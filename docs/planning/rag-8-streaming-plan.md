# RAG-8: Streaming Chat Endpoint Plan

## Status

⚠️ **Planned — not implemented.** Frontend chat currently uses synchronous `POST /api/v1/knowledgebase/chat`. Streaming is optional; implement when UX requires real-time text output.

## API Contract

### New Endpoint

| Method | Endpoint | Auth | Throttle | Returns |
|--------|----------|------|----------|---------|
| POST | `/api/v1/knowledgebase/chat-stream` | Sanctum | 10/min | `text/event-stream` (SSE) |

### Request

```json
{
  "question": "Apa kebijakan cuti tahunan?"
}
```

### SSE Event Format (default SDK)

```
data: {"event":"text_start","invocationId":"...","messageId":"...","timestamp":...}

data: {"event":"text_delta","invocationId":"...","messageId":"...","delta":"Cuti","timestamp":...}

data: {"event":"text_delta","invocationId":"...","messageId":"...","delta":" tahunan","timestamp":...}

data: {"event":"text_end","invocationId":"...","messageId":"...","timestamp":...}

data: {"event":"stream_end","invocationId":"...","finishReason":"stop","usage":{"promptTokens":...,"completionTokens":...,"totalTokens":...},"timestamp":...}

data: [DONE]
```

### Alternative: Vercel AI SDK Protocol

Use `$agent->stream(...)->usingVercelDataProtocol()` for frontend using `ai-sdk` JS library. See https://ai-sdk.dev/docs/ai-sdk-ui/stream-protocol

## Implementation Approach

### 1. Add optional SSE to existing chat response (simplest)

Modify `KnowledgeBaseService::chat()` to accept a `$stream = false` parameter. When `true`, skip the `$agent->prompt()` call and instead:

```php
if ($stream) {
    return $agent->stream($agentPrompt)->usingVercelDataProtocol();
}
```

### 2. Streaming endpoint catches exceptions

Exception in streaming is delivered as `error` event, not thrown. The controller wrapper:

```php
public function chatStream(ChatRequest $request): StreamableAgentResponse
{
    $this->authorize('chat', KnowledgeBase::class);
    return $this->kbService->chat($request->validated('question'), stream: true);
}
```

### 3. Sources/metadata delivery

Embedding + vector search happen synchronously before stream starts. Sources metadata must be embedded in the first SSE event or delivered via separate channel. Options:

- **Option A (Vercel protocol)**: Send sources as part of `data` event before text begins.
- **Option B (custom SSE)**: Emit a `metadata` event with `sources`, `confidence`, `model` before `text_start`.
- **Option C (separate endpoint)**: Frontend calls `chat` first to get metadata, then `chat-stream` for text. Adds latency.
- **Option D (hybrid)**: Return JSON `{sources, confidence}` immediately, then upgrade to SSE for streaming. Complex.

**Recommendation**: Option B — custom first event for metadata:

```
data: {"event":"metadata","sources":[...],"confidence":"high","model":"gemini-2.5-flash"}

data: {"event":"text_start",...}
```

### 4. No pg_trgm fallback in streaming mode

If embedding/LLM fails during streaming, the stream delivers an error event. The frontend should fall back to synchronous `POST /chat` with `?fallback=true` to get keyword results.

### 5. Tests

- `KnowledgeBaseServiceTest`: Add streaming test with `Queue::fake()` + `HrKnowledgeBaseAgent::fake()`, assert `$response instanceof StreamableAgentResponse`
- `KnowledgeBaseEndpointTest`: Add HTTP stream test, assert `Content-Type: text/event-stream` and at least one `data:` line
- `KnowledgeBaseProofTest`: Add auth/permission/throttle integration test for stream endpoint

## Files to Modify

| File | Change |
|------|--------|
| `routes/api.php` | Add `Route::post('/chat-stream', ...)->middleware('throttle:10,1')` |
| `app/Http/Controllers/Api/KnowledgeBaseController.php` | Add `chatStream()` method |
| `app/Services/KnowledgeBaseService.php` | Add `$stream` param to `chat()` |
| `docs/api/api-contracts.md` | Add §12.4 streaming contract |
| Tests (3 files) | Add streaming test coverage |

## Decision

⏸ **Defer implementation** — synchronous chat is sufficient for initial deployment. Implement when:
- Frontend team requests real-time output for UX improvement
- Chat response consistently exceeds 5s (mitigation: optimize first, stream second)
- Long document Q&A is needed (1000+ token responses)
