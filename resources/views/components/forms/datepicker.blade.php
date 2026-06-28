@props([
    'label' => '',
    'name' => '',
    'id' => '',
    'mode' => 'date',
    'required' => false,
])

@php
    $id = $id ?: $name;
@endphp

<x-forms.input :label="$label" :name="$name" :id="$id" type="text" :required="$required"
    data-ui-picker="{{ $mode }}" {{ $attributes }} />
