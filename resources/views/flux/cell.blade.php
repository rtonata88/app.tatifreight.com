@props(['class' => ''])

<td {{ $attributes->merge(['class' => 'px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100 ' . $class]) }}>
    {{ $slot }}
</td>
