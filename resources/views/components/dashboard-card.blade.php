@props(['title', 'count', 'icon'])

<div class="bg-white p-5 my-2 rounded-lg hover:shadow transition duration-150 ease-in-out w-full">
    <div class="flex justify-between items-center">
        <div>
            <h3 class="increase-count text-2xl font-bold">{{ $count ?? $slot }}</h3>
            <p class="text-sm">{{ $title ?? $slot }}</p>
        </div>
        <span class="material-symbols-outlined opacity-20 text-4xl">
            {{ $icon ?? $slot }}
        </span>
    </div>
</div>
