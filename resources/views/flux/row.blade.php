@props(['class' => ''])

<tr {{ $attributes->merge(['class' => 'hover:bg-zinc-50 dark:hover:bg-zinc-800 transition ' . $class]) }}>
    {{ $slot }}
</tr>
