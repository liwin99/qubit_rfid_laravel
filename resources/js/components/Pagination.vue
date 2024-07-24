<script setup>
import { FwbPagination } from 'flowbite-vue'
import { ref } from 'vue'

const props = defineProps({
    data: Object,
})

const emit = defineEmits(['setCurrentPage']);

const currentPage = ref(props.data.current_page)
const totalPages = ref(props.data.last_page)
const show = ref(props.data.per_page < props.data.total)

function pageChanged() {
    emit('setCurrentPage', currentPage.value);
}
</script>

<template>
    <div class="flex justify-end mt-4" v-if="show">
        <fwb-pagination v-model="currentPage" :total-pages="totalPages" previous-label="<" next-label=">" @page-changed="pageChanged"></fwb-pagination>
    </div>
</template>
