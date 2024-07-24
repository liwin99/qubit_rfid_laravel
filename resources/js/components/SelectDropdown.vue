<script>
export default {
    props: [
        'label_name',
        'select_name',
        'data_url',
        'selected_name',
        'selected_id',
    ],
    data() {
        return {
            search: '',
            selectedName: this.selected_name,
            selectedId: this.selected_id,
            isSearching: false,
            isLoading: false,
            data: [],
        };
    },
    watch: {
        search(newSearch) {
            if (newSearch.length > 1) {
                this.getData({ name: newSearch })
            } else {
                this.getData()
            }
        }
    },
    created() {
        this.getData()
    },
    methods: {
        async getData(params = {}) {
            this.isLoading = true

            try {
                const res = await axios.get(this.data_url, { params });
                this.data = res.data
            } catch (error) {
                console.log(error);
                this.data = [];
            } finally {
                this.isLoading = false
            }
        },
        toggleIsSearching() {
            this.isSearching = !this.isSearching
        },
        selectOptions(name, id) {
            this.isSearching = !this.isSearching
            this.selectedName = name
            this.selectedId = id
        },
        clearSelected() {
            this.selectedName = null
            this.selectedId = null
        },
        closeDropdown() {
            if (this.isSearching) {
                this.isSearching = false
            }
        },
    },
}
</script>

<template>
    <div v-click-outside="closeDropdown">
        <div class="flex flex-col w-full justify-between border-gray-400 relative">
            <div class="flex border justify-between p-3 rounded-lg relative">
                <span>{{ selectedName }}</span>
                <div>
                    <button type="button" class="text-gray-500" v-if="selectedName"><i class="fa-regular fa-trash-can"
                            @click="clearSelected"></i></button>
                    <button type="button" class="ml-3 text-gray-500"><i class="fa-solid fa-chevron-down"
                            @click="toggleIsSearching"></i></button>
                </div>
            </div>
            <div v-if="isSearching" class="absolute z-50 bg-white shadow-2xl p-2 rounded-lg"
                style="top: 100%; left: 0; right: 0;">
                <input type="text" class="w-full border rounded-lg border-gray-50" placeholder="Type to Search..."
                    v-model="search" />
                <div class="overflow-y-auto max-h-60">
                    <div v-for="d in data" class="p-2 hover:bg-slate-50 cursor-pointer"
                        @click="selectOptions(d.name, d.id)">
                        {{ d.name }}
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" :name="`${select_name}_name`" v-model="selectedName">
        <input type="hidden" :name="`${select_name}_id`" v-model="selectedId">
    </div>
</template>
