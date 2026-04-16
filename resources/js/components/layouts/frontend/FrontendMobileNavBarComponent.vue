<template>
    <nav class="lg:hidden w-full flex items-center justify-between px-5 py-3 fixed bottom-0 left-0 z-10 shadow-widget bg-white">
        <router-link class="flex flex-col items-center gap-1 text-text transition-all duration-300 hover:text-primary" :class="checkIsPathAndRoutePathSame('/home') ? 'router-link-active router-link-exact-active !text-primary' : ''" :to="{name : 'frontend.home'}">
            <i class="lab-line-home text-lg leading-none"></i>
            <span class="text-xs font-medium capitalize">{{ $t('label.home') }}</span>
        </router-link>

        <button @click.prevent="showTarget('mobile-category-canvas', 'canvas-active')" type="button" class="flex flex-col items-center gap-1 text-text transition-all duration-300 hover:text-primary">
            <i class="lab-line-category text-lg leading-none"></i>
            <span class="text-xs font-medium capitalize">{{ $t('label.categories') }}</span>
        </button>

        <button @click="showTarget('cart-canvas', 'canvas-active')" type="button" class="relative isolate -mt-11">
            <i class="lab-line-bag text-lg w-12 h-12 !leading-12 text-center rounded-full shadow-cart bg-primary text-white"></i>
            <span v-if="carts.length > 0" class="absolute top-5 ltr:right-1.5 rtl:left-1.5 text-[10px] font-medium h-4 px-1 leading-[14px] text-center rounded-full border border-primary bg-[#FFBC1F]">
                {{ carts.length }}
            </span>
        </button>

        <router-link class="flex flex-col items-center gap-1 text-text transition-all duration-300 hover:text-primary" :class="checkIsPathAndRoutePathSame('/wishlist') ? 'router-link-active router-link-exact-active !text-primary' : ''" :to="{ name: 'frontend.wishlist' }">
            <i class="lab-line-heart text-lg leading-none"></i>
            <span class="text-xs font-medium capitalize">{{ $t('menu.wishlist') }}</span>
        </router-link>

        <button v-if="logged" @click.prevent="showTarget('mobile-profile-canvas', 'canvas-active')" type="button" class="flex flex-col items-center gap-1 text-text transition-all duration-300 hover:text-primary">
            <i class="lab-line-user text-lg leading-none"></i>
            <span class="text-xs font-medium capitalize">{{ $t('menu.profile') }}</span>
        </button>

        <router-link v-else class="flex flex-col items-center gap-1 text-text transition-all duration-300 hover:text-primary" :class="checkIsPathAndRoutePathSame('/login') ? 'router-link-active router-link-exact-active !text-primary' : ''" :to="{ name: 'auth.login' }">
            <i class="lab-line-user text-lg leading-none"></i>
            <span class="text-xs font-medium capitalize">{{ $t('menu.login') }}</span>
        </router-link>
        <div v-if="!isPwaViewed" ref="pwaStickyFooter"
            class="lg:hidden border-none bg-white p-4 fixed bottom-0 left-0 w-full z-50 rounded-tl-3xl rounded-tr-3xl shadow-paper">
            <div class="flex items-start gap-3 mb-3">
                <img :src="setting.theme_favicon_logo" alt="theme-favicon-logo"
                    class="w-8 h-8 rounded-lg flex-shrink-0 shadow-xl">
                <h3 class="text-sm flex-auto text-[#008BBA]">
                    {{ $t('message.add') }}
                    {{ setting.company_name }}
                    {{ $t('message.app_to_your_home_screen') }} ?
                </h3>
            </div>
            <div class="flex items-center justify-end gap-2">
                <button @click.prevent="closePwaModal"
                    class="py-2 px-3 rounded-md capitalize text-sm border border-gray-200 text-primary">{{ $t('button.cancel') }}</button>
                <button @click.prevent="closePwaModal" id="installPWAsm"
                    class="py-2 px-3 rounded-md capitalize text-sm bg-primary text-white">{{ $t('button.install') }}</button>
            </div>
        </div>
    </nav>
</template>
<script>
import targetService from "../../../services/targetService";

export default {
    name: "FrontendMobileNavBarComponent",
    data() {
        return {
            loading: {
                isActive: false,
            },
            currentRoute: "",
        }
    },
    computed: {
        logged: function () {
            return this.$store.getters.authStatus;
        },
        carts: function () {
            return this.$store.getters['frontendCart/lists'];
        },
        isPwaViewed: function () {
            return localStorage.getItem('pwa_viewed') ? true : false;
        },
        setting: function () {
            return this.$store.getters['frontendSetting/lists'];
        },
    },
    mounted() {
        this.currentRoute = this.$route.path;
    },
    methods: {
        checkIsPathAndRoutePathSame(path) {
            if (this.currentRoute === path) {
                return true;
            }
        },
        showTarget: function (id, cClass) {
            targetService.showTarget(id, cClass);
        },
        closePwaModal: function () {
            const modalTarget = this.$refs.pwaStickyFooter;
            modalTarget?.classList?.add("hidden");
            localStorage.setItem('pwa_viewed', true);
        }
    },
    watch: {
        $route(to, from) {
            this.currentRoute = to.path;
        },
    }
}
</script>