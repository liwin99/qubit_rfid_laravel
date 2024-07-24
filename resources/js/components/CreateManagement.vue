<script>
import SelectDropdown from './../components/SelectDropdown.vue'

export default {
    emits: ['createdNewManagement'],
    data() {
        return {
            isCreating: false,
            errors: {
                name: '',
                project_name: '',
                location_1_id: '',
                location_2_id: '',
                location_3_id: '',
                location_4_id: '',
            },
            used_for_attendance: false,
        };
    },
    components: {
        SelectDropdown,
    },
    methods: {
        async submitForm(e) {
            let formData = new FormData(e.target);

            const data = Object.fromEntries([...formData.entries()].map(([key, value]) => [key, value === "" ? null : value]));

            data.used_for_attendance = data.used_for_attendance == 'on' ? true : false;

            let validForm = this.validateForm(data);

            if (!validForm) {
                return;
            }

            try {
                const res = await axios.post('/rfid-management', data);

                this.resetErrors();
                this.toggleIsCreating();

                this.$emit("createdNewManagement");
            } catch (errors) {
                if (errors.response) {
                    if (errors.response.data.errors.name) {
                        this.errors.name = errors.response.data.errors.name[0]
                    }
                    if (errors.response.data.errors.project_id) {
                        this.errors.project_id = errors.response.data.errors.project_id[0]
                    }
                    if (errors.response.data.errors.location_1_id) {
                        this.errors.location_1_id = errors.response.data.errors.location_1_id[0]
                    }
                    if (errors.response.data.errors.location_1_id) {
                        this.errors.location_2_id = errors.response.data.errors.location_2_id[0]
                    }
                } else {
                    console.log(errors);
                }
            }
        },
        toggleIsCreating() {
            this.isCreating = !this.isCreating
            this.resetErrors();
        },
        validateForm(data) {
            let isValid = true;

            // Reset errors
            this.resetErrors();

            if (!data.name) {
                this.errors.name = 'Reader is required.';
                isValid = false;
            }

            if (!data.project_id) {
                this.errors.project_id = 'Project is required.';
                isValid = false;
            }

            if (!data.location_1_id) {
                this.errors.location_1_id = 'Location 1 is required.';
                isValid = false;
            }

            if (!data.location_2_id) {
                this.errors.location_2_id = 'Location 2 is required.';
                isValid = false;
            }

            if (!!data.location_4_id && !data.location_3_id) {
                this.errors.location_3_id = 'Location 3 is required if location 4 is set.';
                isValid = false;
            }

            const uniqueValues = new Set();

            for (let i = 1; i <= 4; i++) {
                const locationId = `location_${i}_id`;
                const value = data[locationId];

                // Validate uniqueness only if the field is not empty
                if (value !== null) {
                    if (uniqueValues.has(value)) {
                        this.errors[locationId] = `Locations must be unique.`;
                        isValid = false;
                    } else {
                        uniqueValues.add(value);
                    }
                }
            }


            return isValid;
        },
        resetErrors() {
            Object.keys(this.errors).forEach(field => {
                this.errors[field] = '';
            });
        },
    }
}
</script>

<template>
    <Transition name="slide-fade">
        <div class="text-right" v-if="!isCreating">
            <button type="button" @click="toggleIsCreating"
                class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                Create New
            </button>
        </div>

        <div v-else>
            <form @submit.prevent="submitForm($event)" id="create-form"
                class="bg-white shadow-2xl border-gray-300 border-2 rounded-lg p-5 mt-4 transition duration-300 ease-in-out">
                <div class="flex flex-wrap gap-2 flex-grow">
                    <div class="w-full md:w-1/3 md:flex-1">
                        <label for="name">Reader</label>
                        <input type="text" name="name" id="name"
                            class="border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 w-full focus:border-blue-500 block p-3"
                            placeholder="Reader...">
                        <div v-if="errors.name" class="text-xs" style="color: red;">{{ errors.name }}</div>
                    </div>
                    <div class="w-full md:w-1/3 md:flex-1">
                        <label for="project_id">Project</label>
                        <SelectDropdown label_name="Project" select_name="project" data_url="/ajax/get-projects" />
                        <div v-if="errors.project_id" class="text-xs" style="color: red;">{{ errors.project_id }}</div>
                    </div>
                    <div class="w-full md:flex md:flex-wrap md:gap-2 md:flex-grow md:mt-4">
                        <div class="mb-3 md:w-1/3 md:flex-1">
                            <label for="location_1_id">Location 1</label>
                            <SelectDropdown label_name="Location 1" select_name="location_1"
                                data_url="/ajax/get-locations" />
                            <div v-if="errors.location_1_id" class="text-xs" style="color: red;">{{ errors.location_1_id
                            }}</div>
                        </div>
                        <div class="mb-3 md:w-1/3 md:flex-1">
                            <label for="location_2_id">Location 2</label>
                            <SelectDropdown label_name="Location 2" select_name="location_2"
                                data_url="/ajax/get-locations" />
                            <div v-if="errors.location_2_id" class="text-xs" style="color: red;">{{ errors.location_2_id
                            }}</div>
                        </div>
                        <div class="mb-3 md:w-1/3 md:flex-1">
                            <label for="location_3_id">Location 3</label>
                            <SelectDropdown label_name="Location 3" select_name="location_3"
                                data_url="/ajax/get-locations" />
                            <div v-if="errors.location_3_id" class="text-xs" style="color: red;">{{ errors.location_3_id
                            }}</div>
                        </div>
                        <div class="mb-3 md:w-1/3 md:flex-1">
                            <label for="location_4_id">Location 4</label>
                            <SelectDropdown label_name="Location 4" select_name="location_4"
                                data_url="/ajax/get-locations" />
                            <div v-if="errors.location_4_id" class="text-xs" style="color: red;">{{ errors.location_4_id
                            }}</div>
                        </div>
                    </div>
                </div>
                <div class="mt-3 mb-5 flex items-center">
                    <input id="used_for_attendance" name="used_for_attendance" type="checkbox" v-model="used_for_attendance" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                    <label for="used_for_attendance" class="ms-2 text-gray-900 dark:text-gray-300">Used For Attendance</label>
                </div>
                <button type="submit"
                    class="mr-1 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                    Save
                </button>
                <button type="button" @click="toggleIsCreating"
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                    Cancel
                </button>
            </form>
        </div>
    </Transition>
</template>

<style>
.slide-fade-enter-active {
    transition: all 0.2s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateX(20px);
    opacity: 0;
}
</style>
