#!/usr/bin/env python3
"""
HRConnect UI audit fixer.
Maps raw Tailwind palette classes to MD3 design tokens and strips dead `dark:` variants
(dark mode is disabled per design system). Operates on all Blade files under resources/views.

Token mapping (light-mode only):
  slate-*  -> ink / ink-2 / paper / paper-2 / rule / paper-3
  gray-*   -> ink / ink-2 / paper / paper-2 / paper-3 / rule
  blue-*   -> accent / accent-ink
  emerald-*/green-* -> success
  amber-*/yellow-*  -> warning
  rose-*/red-*      -> error
  purple-*          -> accent (secondary)
  white/black       -> paper / ink (as bg/text only when paired with palette)

The script only replaces known palette color utilities, preserving structural classes.
"""
import os
import re

VIEWS = "/home/merger/hrconnect/resources/views"

# Map tailwind palette families -> MD3 token. We keep numeric shades loosely:
# 50/100/200 -> light tints (bg-/text- with /10 or token-2), 300-500 -> mid, 600-900 -> dark/ink
# Simpler: replace the color NAME portion, keep utility prefix + shade semantics via opacity where sane.

# Color name -> (bg_token, text_token, border_token, ring_token)
# For tints (50-200) we use token/10 backgrounds and token text.
# We'll handle by replacing the color word in each utility class.

REPLACEMENTS = {
    # slate family
    "slate-50": "paper-2",
    "slate-100": "paper-2",
    "slate-200": "rule",
    "slate-300": "ink-2",
    "slate-400": "ink-2",
    "slate-500": "ink-2",
    "slate-600": "ink-2",
    "slate-700": "ink",
    "slate-800": "ink",
    "slate-900": "ink",
    "slate-950": "ink",
    # gray family
    "gray-50": "paper-2",
    "gray-100": "paper-2",
    "gray-200": "rule",
    "gray-300": "ink-2",
    "gray-400": "ink-2",
    "gray-500": "ink-2",
    "gray-600": "ink-2",
    "gray-700": "ink",
    "gray-800": "ink",
    "gray-900": "ink",
    "gray-950": "ink",
    # blue family -> accent
    "blue-50": "accent/10",
    "blue-100": "accent/10",
    "blue-200": "accent/20",
    "blue-300": "accent",
    "blue-400": "accent",
    "blue-500": "accent",
    "blue-600": "accent",
    "blue-700": "accent",
    "blue-800": "accent",
    "blue-900": "accent",
    # emerald / green -> success
    "emerald-50": "success/10",
    "emerald-100": "success/10",
    "emerald-200": "success/20",
    "emerald-300": "success",
    "emerald-400": "success",
    "emerald-500": "success",
    "emerald-600": "success",
    "emerald-700": "success",
    "emerald-800": "success",
    "emerald-900": "success",
    "green-50": "success/10",
    "green-100": "success/10",
    "green-200": "success/20",
    "green-300": "success",
    "green-400": "success",
    "green-500": "success",
    "green-600": "success",
    "green-700": "success",
    "green-800": "success",
    "green-900": "success",
    # amber / yellow -> warning
    "amber-50": "warning/10",
    "amber-100": "warning/10",
    "amber-200": "warning/20",
    "amber-300": "warning",
    "amber-400": "warning",
    "amber-500": "warning",
    "amber-600": "warning",
    "amber-700": "warning",
    "amber-800": "warning",
    "amber-900": "warning",
    "yellow-50": "warning/10",
    "yellow-100": "warning/10",
    "yellow-200": "warning/20",
    "yellow-300": "warning",
    "yellow-400": "warning",
    "yellow-500": "warning",
    "yellow-600": "warning",
    "yellow-700": "warning",
    "yellow-800": "warning",
    "yellow-900": "warning",
    # rose / red -> error
    "rose-50": "error/10",
    "rose-100": "error/10",
    "rose-200": "error/20",
    "rose-300": "error",
    "rose-400": "error",
    "rose-500": "error",
    "rose-600": "error",
    "rose-700": "error",
    "rose-800": "error",
    "rose-900": "error",
    "red-50": "error/10",
    "red-100": "error/10",
    "red-200": "error/20",
    "red-300": "error",
    "red-400": "error",
    "red-500": "error",
    "red-600": "error",
    "red-700": "error",
    "red-800": "error",
    "red-900": "error",
    # purple / indigo / violet -> accent (secondary emphasis)
    "purple-50": "accent/10",
    "purple-100": "accent/10",
    "purple-200": "accent/20",
    "purple-300": "accent",
    "purple-400": "accent",
    "purple-500": "accent",
    "purple-600": "accent",
    "purple-700": "accent",
    "purple-800": "accent",
    "purple-900": "accent",
    "indigo-50": "accent/10",
    "indigo-100": "accent/10",
    "indigo-200": "accent/20",
    "indigo-300": "accent",
    "indigo-400": "accent",
    "indigo-500": "accent",
    "indigo-600": "accent",
    "indigo-700": "accent",
    "indigo-800": "accent",
    "indigo-900": "accent",
}

# Sort by length descending so "slate-900" matches before "slate-9" (no such, but safe)
SORTED_KEYS = sorted(REPLACEMENTS.keys(), key=len, reverse=True)


def replace_classes(text: str) -> str:
    # Match utility classes like: bg-slate-900, text-slate-500, border-slate-200,
    # ring-slate-500, from-slate-900, via-slate-900, divide-slate-200, hover:bg-slate-800,
    # dark:bg-slate-900, focus:ring-slate-500, etc.
    # Pattern: optional variant prefix (word: or hover:/focus:/group-hover:/peer-checked: etc),
    # then a utility prefix (bg|text|border|ring|divide|from|via|to|fill|stroke|placeholder|outline|accent|caret|shadow|decoration),
    # then the color token, optionally with /opacity.
    # We replace the color token portion only.

    def repl(m):
        prefix = m.group(1)  # variant + utility, e.g. "dark:bg-" or "hover:border-"
        color = m.group(2)
        opacity = m.group(3) or ""
        new_color = REPLACEMENTS.get(color)
        if new_color is None:
            return m.group(0)
        # If new_color already contains "/10" style and original had no opacity, keep as-is.
        # If original had opacity like /20 and new is "accent", combine: accent/20.
        if opacity:
            if "/" in new_color:
                # new already has opacity; drop extra opacity to avoid "accent/10/20"
                return prefix + new_color
            return prefix + new_color + opacity
        return prefix + new_color

    # Build alternation of color tokens
    color_alt = "(?:" + "|".join(re.escape(k) for k in SORTED_KEYS) + ")"
    # variant prefix: (?:[a-z-]+:)*  utility: (bg|text|border|ring|divide|from|via|to|fill|stroke|placeholder|outline|accent|caret|shadow|decoration|stroke|odd|even|first|last|visited|active|disabled|focus|hover|group-hover|peer|peer-checked|peer-focus|group-focus|checked|required|invalid|default|enabled|read-only|focus-within|focus-visible|motion-safe|motion-reduce|supports|aria|data|ltr|rtl|print|contrast|forced|target|not)
    variant = r"(?:[a-zA-Z-]+:)*"
    util = r"(bg|text|border|ring|divide|from|via|to|fill|stroke|placeholder|outline|accent|caret|shadow|decoration|odd|even|first|last|visited|active|disabled|focus|hover|group-hover|peer|peer-checked|peer-focus|group-focus|checked|required|invalid|default|enabled|read-only|focus-within|focus-visible|target|not)"
    pattern = re.compile(r"\b(" + variant + util + r"-)(" + color_alt + r")(?:/(\d+))?\b")
    return pattern.sub(repl, text)


def strip_dark(text: str) -> str:
    # Remove `dark:` variant prefixes from class attributes (dead in light-only mode)
    # Only strip the variant token, keep the rest of the class.
    return re.sub(r"\bdark:\b", "", text)


def main():
    changed = 0
    for root, _dirs, files in os.walk(VIEWS):
        for fn in files:
            if not fn.endswith(".blade.php"):
                continue
            path = os.path.join(root, fn)
            with open(path, "r", encoding="utf-8") as f:
                original = f.read()
            text = replace_classes(original)
            text = strip_dark(text)
            # Collapse accidental double spaces from removed dark: and tidy up
            text = re.sub(r"\s{2,}", " ", text)
            if text != original:
                with open(path, "w", encoding="utf-8") as f:
                    f.write(text)
                changed += 1
                print(f"updated: {os.path.relpath(path, VIEWS)}")
    print(f"\nTotal files changed: {changed}")


if __name__ == "__main__":
    main()
