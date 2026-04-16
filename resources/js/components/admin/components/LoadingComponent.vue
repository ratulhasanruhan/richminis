<template>
    <VueElementLoading spinner="bar-fade-scale" color="#FD8B0E" :active="props.isActive" :is-full-screen="true" />
</template>

<script>
import VueElementLoading from 'vue-element-loading';

export default {
    name: "LoadingComponent",
    components: { VueElementLoading },
    props: ['props'],
    data() {
        return {
            isActive: false
        }
    }
}
</script>