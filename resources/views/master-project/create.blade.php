<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Master Project') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white p-4 py-10 rounded-lg">
                <x-validation-errors class="mb-8" />

                <form action="{{ route('master.project.store') }}" method="POST">
                    @csrf
                    <div class="mb-5">
                        <label for="name" class="block mb-2 text-sm font-medium text-gray-900">Name</label>
                        <input type="text" id="name" name="name"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                            placeholder="Name" value="{{ old('name') }}" required>
                    </div>
                    <div class="mb-5">
                        <label for="daily_period_from" class="block mb-2 text-sm font-medium text-gray-900">Reporting Period From</label>
                        <input type="time" id="daily_period_from" name="daily_period_from" value="06:00"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                            value="{{ old('daily_period_from') }}" required>
                    </div>
                    <div class="mb-5">
                        <label for="daily_period_to" class="block mb-2 text-sm font-medium text-gray-900">Reporting Period To</label>
                        <input type="time" id="daily_period_to" name="daily_period_to" value="05:59"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                            value="{{ old('daily_period_to') }}" required>
                    </div>
                    <button type="submit"
                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        Submit
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
