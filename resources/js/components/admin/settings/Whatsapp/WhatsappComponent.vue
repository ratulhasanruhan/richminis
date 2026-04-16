<template>
    <LoadingComponent :props="loading" />

    <div id="restaurant_state" class="db-card db-tab-div active">
        <div class="db-card-header">
            <h3 class="db-card-title">{{ $t("menu.whatsapp_order_setup") }}</h3>
        </div>
        <div class="db-card-body">
            <form @submit.prevent="save">
                <div class="form-row">
                    <div class="form-col-12">
                        <label class="db-field-title required">{{ $t("label.status") }}</label>
                        <div class="db-field-radio-group">
                            <div class="db-field-radio">
                                <div class="custom-radio">
                                    <input type="radio" v-model="form.whatsapp_status" id="open"
                                        :value="enums.activityEnum.ENABLE" class="custom-radio-field">
                                    <span class="custom-radio-span"></span>
                                </div>
                                <label for="open" class="db-field-label">{{ $t('label.enable') }}</label>
                            </div>
                            <div class="db-field-radio">
                                <div class="custom-radio">
                                    <input type="radio" v-model="form.whatsapp_status" id="close"
                                        :value="enums.activityEnum.DISABLE" class="custom-radio-field">
                                    <span class="custom-radio-span"></span>
                                </div>
                                <label for="close" class="db-field-label">{{ $t('label.disable') }}</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-col-12" v-if="form.whatsapp_status === enums.activityEnum.ENABLE">

                        <label for="whatsapp_number" class="db-field-title required">
                            {{ $t("label.whatsapp_number") }}
                        </label>
                        <div :class="errors.whatsapp_number ? 'invalid' : ''"
                            class="db-field-control flex items-center">
                            <div class="w-fit flex-shrink-0 dropdown-group">
                                <button type="button" class="flex items-center gap-1 dropdown-btn">
                                    {{ flag }}
                                    <span class="whitespace-nowrap flex-shrink-0 text-xs">{{
                                        form.whatsapp_calling_code
                                    }}</span>
                                    <i class="fa-solid fa-caret-down text-xs"></i>
                                </button>
                                <ul
                                    class="p-1.5 w-24 rounded-lg shadow-xl absolute top-8 -left-4 z-10 border border-gray-200 bg-white scale-y-0 origin-top dropdown-list !h-52 !overflow-x-hidden !overflow-y-auto thin-scrolling">
                                    <li v-for="countryCode in countryCodes" @click="change(countryCode)"
                                        class="flex items-center gap-2 p-1.5 rounded-md cursor-pointer hover:bg-gray-100">
                                        {{ countryCode.flag_emoji }}
                                        <span class="whitespace-nowrap text-xs">{{ countryCode.calling_code
                                            }}</span>
                                    </li>
                                </ul>

                            </div>
                            <input v-model="form.whatsapp_number" v-on:keypress="phoneNumber($event)" v-bind:class="errors.whatsapp_number
                                ? 'invalid' : ''" type="text" id="phone" class="pl-2 text-sm w-full h-full" />
                        </div>

                        <small class="db-field-alert" v-if="errors.whatsapp_number">{{ errors.whatsapp_number[0]
                            }}</small>
                    </div>


                    <div class="form-col-12">
                        <button type="submit" class="db-btn text-white bg-primary">
                            <i class="lab lab-fill-save"></i>
                            <span>{{ $t("button.save") }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import LoadingComponent from "../../components/LoadingComponent";
import alertService from "../../../../services/alertService";
import appService from "../../../../services/appService";
import activityEnum from "../../../../enums/modules/activityEnum";

export default {
    name: "WhatsappComponent",
    components: { LoadingComponent },
    data() {
        return {
            loading: {
                isActive: false,
            },
            form: {
                whatsapp_status: null,
                whatsapp_number: null,
                whatsapp_calling_code: null,
            },
            enums: {
                activityEnum: activityEnum,
            },
            flag: '',
            errors: {},
        };
    },
    mounted() {
        try {
            this.loading.isActive = true;
            this.$store.dispatch("whatsapp/lists").then((res) => {
                this.form.whatsapp_calling_code = res.data.data.whatsapp_calling_code;
                this.form.whatsapp_status = res.data.data.whatsapp_status;
                this.form.whatsapp_number = res.data.data.whatsapp_number;
                if (res.data.data.whatsapp_calling_code !== "") {
                    this.$store.dispatch('countryCode/callingCode', res.data.data.whatsapp_calling_code).then(res => {
                        this.flag = res.data.data.flag_emoji;
                        this.loading.isActive = false;
                    }).catch((err) => {
                        this.loading.isActive = false;
                    });
                }
                this.loading.isActive = false;
            }).catch((err) => {
                this.loading.isActive = false;
            });
        } catch (err) {
            this.loading.isActive = false;
            alertService.error(err);
        }
        this.fetchCallingCode();
    },
    computed: {
        countryCodes: function () {
            return this.$store.getters['countryCode/lists'];
        },
    },
    methods: {
        phoneNumber(e) {
            return appService.phoneNumber(e);
        },
        change: function (e) {
            this.flag = e.flag_emoji;
            this.form.whatsapp_calling_code = e.calling_code;
        },
        fetchCallingCode: async function () {
            try {
                this.loading.isActive = true;
                await this.$store.dispatch('countryCode/lists').then(res => {
                    if (this.form.whatsapp_calling_code === "") {
                        this.flag = res.data.data[0].flag_emoji;
                        this.form.whatsapp_calling_code = res.data.data[0].calling_code;
                    }
                });

            } catch (err) {
                this.loading.isActive = false;
                alertService.error(err);
            }
        },
        save: function () {
            try {
                this.loading.isActive = true;
                this.$store.dispatch("whatsapp/save", this.form).then((res) => {
                    this.loading.isActive = false;
                    alertService.successFlip(res.config.method === "put" ?? 0, this.$t("menu.whatsapp_order_setup"));
                    this.errors = {};
                }).catch((err) => {
                    this.loading.isActive = false;
                    this.errors = err.response.data.errors;
                });
            } catch (err) {
                this.loading.isActive = false;
                alertService.error(err);
            }
        },
    },
};
</script>