@props([
    'label' => '',
    'name' => '',
    'id' => '',
    'mode' => 'date',
    'required' => false,
    'placeholder' => 'Pilih tanggal',
])

@php
    $id = $id ?: $name;
@endphp

<x-forms.input :label="$label" :name="$name" :id="$id" type="text" :required="$required" :placeholder="$placeholder"
    data-ui-picker="{{ $mode }}" {{ $attributes }} />
