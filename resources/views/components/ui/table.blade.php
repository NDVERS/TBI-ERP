@props([
    'headers' => [],
    'stickyHeader' => false,
])

<div class="w-full overflow-hidden border border-zinc-200 rounded-lg bg-white shadow-xs">
    <div class="overflow-x-auto">
        <table {{ $attributes->merge(['class' => 'w-full text-left border-collapse text-sm text-zinc-800']) }}>
            @if (!empty($headers))
                <thead class="bg-zinc-50 border-b border-zinc-200 text-xs font-medium text-zinc-600 uppercase tracking-wider {{ $stickyHeader ? 'sticky top-0 z-10' : '' }}">
                    <tr>
                        @foreach ($headers as $header)
                            <th scope="col" class="px-4 py-3 first:pl-5 last:pr-5 font-semibold text-zinc-700 whitespace-nowrap">
                                {{ $header }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
            @endif

            <tbody class="divide-y divide-zinc-200 bg-white font-normal">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
