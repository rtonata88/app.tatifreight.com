@props(['class' => ''])

<thead {{ $attributes->merge(['class' => 'bg-zinc-50 dark:bg-zinc-800 ' . $class]) }}>
    <tr>
        {{ $slot }}
    </tr>
</thead>
