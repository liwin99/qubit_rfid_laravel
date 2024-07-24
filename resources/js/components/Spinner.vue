<script>
import { HalfCircleSpinner } from 'epic-spinners'

export default {
    components: {
        HalfCircleSpinner,
    },
}
</script>

<template>
    <div class="mt-2 flex justify-center items-center rounded-lg" style="height: 250px;">
        <half-circle-spinner :animation-duration="1000" :size="30" color="#7f7f7f" />
    </div>
</template>

