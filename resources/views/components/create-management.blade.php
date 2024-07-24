<div x-data="{ showCreate: false  }"
    @add_new_management.window="showCreate = true"
    x-init="showCreate = <?php if($errors->storeNewReader->any()) { echo 'true'; } else { echo 'false'; } ?>">
    <form x-cloak x-show="showCreate" x-transition:enter="transition ease-in duration-300"
        x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-out duration-300" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90" action="{{ route('rfid.management.store') }}" method="POST"
        class="bg-white shadow-2xl border-gray-400 border-2 rounded-lg p-5 mt-4">
        @csrf
        <div class="flex flex-wrap gap-2 flex-grow">
            <div class="w-full md:w-1/3 md:flex-1">
                <label for="reader">Reader</label>
                @php $readerName = old('reader_name') ?? ''; $readerId = old('reader_id') ?? ''; @endphp
                <livewire:search-dropdown model='reader' :placeHolderName="$readerName" :placeHolderId="$readerId"/>
            </div>
            <div class="w-full md:w-1/3 md:flex-1">
                <label for="project">Project</label>
                @php $projectName = old('project_name') ?? ''; $projectId = old('project_id') ?? ''; @endphp
                <livewire:search-dropdown model='project' :placeHolderName="$projectName" :placeHolderId="$projectId"/>
            </div>
            <div class="w-full md:flex md:flex-wrap md:gap-2 md:flex-grow md:mt-4">
                <div class="mb-3 md:w-1/3 md:flex-1">
                    <label for="location_1">Location 1</label>
                    @php $location1Name = old('location_1_name') ?? ''; $location1Id = old('location_1_id') ?? ''; @endphp
                    <livewire:search-dropdown model='location_1' :placeHolderName="$location1Name" :placeHolderId="$location1Id"/>
                </div>
                <div class="mb-3 md:w-1/3 md:flex-1">
                    <label for="location_2">Location 2</label>
                    @php $location2Name = old('location_2_name') ?? ''; $location2Id = old('location_2_id') ?? ''; @endphp
                    <livewire:search-dropdown model='location_2' :placeHolderName="$location2Name" :placeHolderId="$location2Id"/>
                </div>
                <div class="mb-3 md:w-1/3 md:flex-1">
                    <label for="location_3">Location 3</label>
                    @php $location3Name = old('location_3_name') ?? ''; $location3Id = old('location_3_id') ?? ''; @endphp
                    <livewire:search-dropdown model='location_3' :placeHolderName="$location3Name" :placeHolderId="$location3Id"/>
                </div>
                <div class="mb-3 md:w-1/3 md:flex-1">
                    <label for="location_4">Location 4</label>
                    @php $location4Name = old('location_4_name') ?? ''; $location4Id = old('location_4_id') ?? ''; @endphp
                    <livewire:search-dropdown model='location_4' :placeHolderName="$location4Name" :placeHolderId="$location4Id"/>
                </div>
            </div>
        </div>
        <button type="submit"
            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
            Save
        </button>
        <button type="button" x-on:click="showCreate = false; $dispatch('cancel_management')"
            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
            Cancel
        </button>
    </form>
</div>
