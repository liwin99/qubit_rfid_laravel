<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="w-full md:flex md:gap-4">
                <x-dashboard-card title="Projects" count="{{ $projects }}" icon="view_list" />
                <x-dashboard-card title="Locations" count="{{ $locations }}" icon="location_on" />
                <x-dashboard-card title="Devices" count="{{ $readers }}" icon="devices" />
            </div>
        </div>
    </div>
</x-app-layout>
