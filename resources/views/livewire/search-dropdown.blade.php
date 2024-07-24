<div x-data="{ open: false }" @click.away="open = false" @close.stop="open = false" class="w-full">
    <div class="flex w-full border p-3 justify-between rounded-lg">
        <div class="self-center">{{ $placeHolderName }}</div>
        <div class="p-1">
            @if ($placeHolderName)
                <span class="text-gray-500 cursor-pointer" wire:click="removeInput" ><i class="fa-regular fa-trash-can"></i></span>
            @endif
            <span class="text-base cursor-pointer mx-2 text-gray-500" @click="open = ! open"><i
                    class="fa-solid fa-plus"></i></span>
        </div>
    </div>

    <div x-show="open" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute z-50 mt-2 rounded-md shadow-lg p-2 bg-white w-max" style="display: none;">
        <input type="text" wire:model.live.debounce.800ms="search" class="w-full border rounded-lg" placeholder="Type to Search..." />

        <div class="w-full overflow-y-auto max-h-60" wire:loading.class="opacity-50">
            @foreach ($results as $result)
                <div class="hover:bg-slate-50 mb-2 p-2 cursor-pointer" data-id="{{ $result->id }}"
                    data-name="{{ $result->name }}" wire:click="setInput('{{ $result->id }}', '{{ $result->name }}')"
                    @click="open = false">
                    {{ $result->name }}
                </div>
            @endforeach
        </div>
    </div>

    <input type="hidden" name="{{ $inputName }}_name" value="{{ $placeHolderName }}">
    <input type="hidden" name="{{ $inputName }}_id" value="{{ $placeHolderId }}">
</div>
