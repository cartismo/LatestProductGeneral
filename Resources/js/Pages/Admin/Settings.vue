<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StoreSettingsTabs from '@/Components/Admin/StoreSettingsTabs.vue';
import {
    ClockIcon,
    ArrowLeftIcon,
    ArrowPathIcon,
    PlusIcon,
    TrashIcon,
    EyeIcon,
    EyeSlashIcon,
    Bars3Icon,
    MagnifyingGlassIcon,
    BoltIcon,
} from '@heroicons/vue/24/outline';
import { CheckIcon } from '@heroicons/vue/24/solid';
import draggable from 'vuedraggable';

const props = defineProps({
    module: Object,
    stores: Array,
    storeSettings: Object,
    defaultSettings: Object,
    latestProducts: Array,
    products: Array,
});

const storeTabsRef = ref(null);
const saving = ref(false);
const searchQuery = ref('');
const showProductSelector = ref(false);

const submit = () => {
    if (!storeTabsRef.value) return;

    saving.value = true;
    router.put(route('admin.general.latest.settings.update'), {
        store_id: storeTabsRef.value.activeStoreId,
        is_enabled: storeTabsRef.value.isEnabled,
        settings: storeTabsRef.value.localSettings,
    }, {
        preserveScroll: true,
        onFinish: () => saving.value = false,
    });
};

const resetAll = () => {
    if (confirm('Reset all settings to defaults?') && storeTabsRef.value) {
        Object.assign(storeTabsRef.value.localSettings, props.defaultSettings);
    }
};

const hasChanges = computed(() => {
    if (!storeTabsRef.value) return false;
    const currentStoreSettings = props.storeSettings[storeTabsRef.value.activeStoreId];
    if (!currentStoreSettings) return true;
    const original = { ...props.defaultSettings, ...(currentStoreSettings.settings || {}) };
    return JSON.stringify(storeTabsRef.value.localSettings) !== JSON.stringify(original) ||
           storeTabsRef.value.isEnabled !== currentStoreSettings.is_enabled;
});

const availableProducts = computed(() => {
    const selectedIds = props.latestProducts.map(lp => lp.product_id);
    return props.products.filter(p => {
        const notSelected = !selectedIds.includes(p.id);
        const matchesSearch = !searchQuery.value ||
            p.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            p.sku?.toLowerCase().includes(searchQuery.value.toLowerCase());
        return notSelected && matchesSearch;
    });
});

const addProduct = (productId) => {
    router.post(route('admin.general.latest.products.add'), {
        product_id: productId,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showProductSelector.value = false;
            searchQuery.value = '';
        },
    });
};

const removeProduct = (latestProduct) => {
    if (confirm('Remove this product from latest?')) {
        router.delete(route('admin.general.latest.products.remove', latestProduct.id), {
            preserveScroll: true,
        });
    }
};

const toggleProduct = (latestProduct) => {
    router.post(route('admin.general.latest.products.toggle', latestProduct.id), {}, {
        preserveScroll: true,
    });
};

const onDragEnd = () => {
    const productsOrder = props.latestProducts.map((lp, index) => ({
        id: lp.id,
        sort_order: index,
    }));

    router.post(route('admin.general.latest.products.reorder'), {
        products: productsOrder,
    }, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AdminLayout :title="`${module.name} Settings`">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('admin.modules.installed.index')"
                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div class="flex items-center space-x-3">
                        <div class="p-3 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl shadow-lg shadow-emerald-500/25">
                            <ClockIcon class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">New Arrivals</h1>
                            <p class="text-sm text-gray-500">Display latest products on your store</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span v-if="hasChanges" class="flex items-center text-sm text-amber-600 font-medium">
                        <span class="w-2 h-2 bg-amber-500 rounded-full mr-2 animate-pulse"></span>
                        Unsaved changes
                    </span>
                    <button
                        type="button"
                        @click="resetAll"
                        class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors"
                    >
                        <ArrowPathIcon class="w-4 h-4 inline mr-2" />
                        Reset
                    </button>
                    <button
                        type="button"
                        @click="submit"
                        :disabled="saving || !hasChanges"
                        class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl hover:from-emerald-600 hover:to-teal-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg shadow-emerald-500/25"
                    >
                        <CheckIcon class="w-4 h-4 inline mr-2" />
                        {{ saving ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </div>
        </template>

        <StoreSettingsTabs ref="storeTabsRef" :stores="stores" :store-settings="storeSettings" :default-settings="defaultSettings" module-slug="latest-product-general">
            <template #default="{ store, settings, updateSetting, isEnabled }">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Settings Form -->
                    <div class="lg:col-span-1 space-y-6">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                <h3 class="font-semibold text-gray-900">Settings</h3>
                            </div>
                            <div class="p-6 space-y-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="font-medium text-gray-900">Enabled</h4>
                                        <p class="text-sm text-gray-500">Show new arrivals section</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="updateSetting('enabled', !settings.enabled)"
                                        :class="settings.enabled ? 'bg-green-500' : 'bg-gray-300'"
                                        class="relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors"
                                    >
                                        <span
                                            :class="settings.enabled ? 'translate-x-5' : 'translate-x-0'"
                                            class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg transition"
                                        />
                                    </button>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Section Title</label>
                                    <input
                                        type="text"
                                        :value="settings.title"
                                        @input="updateSetting('title', $event.target.value)"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Max Products</label>
                                    <input
                                        type="number"
                                        :value="settings.max_products"
                                        @input="updateSetting('max_products', parseInt($event.target.value) || 0)"
                                        min="1"
                                        max="50"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                    />
                                </div>

                                <div class="border-t border-gray-200 pt-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center space-x-2">
                                            <BoltIcon class="w-5 h-5 text-emerald-500" />
                                            <h4 class="font-medium text-gray-900">Auto Select</h4>
                                        </div>
                                        <button
                                            type="button"
                                            @click="updateSetting('auto_select', !settings.auto_select)"
                                            :class="settings.auto_select ? 'bg-emerald-500' : 'bg-gray-300'"
                                            class="relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors"
                                        >
                                            <span
                                                :class="settings.auto_select ? 'translate-x-5' : 'translate-x-0'"
                                                class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg transition"
                                            />
                                        </button>
                                    </div>
                                    <p class="text-sm text-gray-500 mb-4">Automatically include products created within the last X days</p>

                                    <div v-if="settings.auto_select">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Days as "New"</label>
                                        <div class="flex items-center space-x-3">
                                            <input
                                                type="range"
                                                :value="settings.days_as_new"
                                                @input="updateSetting('days_as_new', parseInt($event.target.value))"
                                                min="7"
                                                max="90"
                                                step="7"
                                                class="flex-1"
                                            />
                                            <span class="text-sm font-mono text-gray-600 w-16">{{ settings.days_as_new }} days</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-emerald-50 rounded-2xl p-5 border border-emerald-100">
                            <h4 class="text-sm font-medium text-emerald-900 mb-2">How it works</h4>
                            <p class="text-sm text-emerald-700">
                                <strong>Auto Select ON:</strong> Products created in the last {{ settings.days_as_new }} days are automatically shown.<br><br>
                                <strong>Manual products</strong> added below are always included, regardless of auto-select setting.
                            </p>
                        </div>
                    </div>

                    <!-- Manual Products Selection -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <h3 class="font-semibold text-gray-900">Manual Selections</h3>
                                    <span class="px-2 py-0.5 text-xs font-medium bg-emerald-100 text-emerald-700 rounded-full">
                                        {{ latestProducts.length }}
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    @click="showProductSelector = !showProductSelector"
                                    class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl hover:from-emerald-600 hover:to-teal-700 transition-all"
                                >
                                    <PlusIcon class="w-4 h-4 inline mr-2" />
                                    Add Product
                                </button>
                            </div>

                            <!-- Product Selector Dropdown -->
                            <div v-if="showProductSelector" class="p-4 border-b border-gray-200 bg-emerald-50">
                                <div class="relative mb-3">
                                    <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                                    <input
                                        type="text"
                                        v-model="searchQuery"
                                        placeholder="Search products..."
                                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500"
                                    />
                                </div>
                                <div class="max-h-48 overflow-y-auto space-y-1">
                                    <button
                                        v-for="product in availableProducts.slice(0, 10)"
                                        :key="product.id"
                                        type="button"
                                        @click="addProduct(product.id)"
                                        class="w-full text-left px-3 py-2 text-sm hover:bg-emerald-100 rounded-lg flex items-center justify-between"
                                    >
                                        <span>{{ product.name }}</span>
                                        <span class="text-gray-400 text-xs">{{ product.sku }}</span>
                                    </button>
                                    <p v-if="availableProducts.length === 0" class="text-sm text-gray-500 text-center py-2">
                                        No products available
                                    </p>
                                </div>
                            </div>

                            <div class="p-6">
                                <p class="text-sm text-gray-500 mb-4">
                                    These products are always shown in the "New Arrivals" section, regardless of their creation date.
                                </p>

                                <!-- Empty State -->
                                <div v-if="latestProducts.length === 0" class="text-center py-8">
                                    <ClockIcon class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                                    <p class="text-gray-500">No manual selections</p>
                                    <p class="text-sm text-gray-400">Add products to always include them</p>
                                </div>

                                <!-- Products List -->
                                <draggable
                                    v-else
                                    :list="latestProducts"
                                    item-key="id"
                                    handle=".drag-handle"
                                    @end="onDragEnd"
                                    class="space-y-3"
                                >
                                    <template #item="{ element: lp }">
                                        <div class="flex items-center bg-gray-50 rounded-xl p-4 hover:bg-gray-100 transition-colors">
                                            <div class="drag-handle cursor-grab active:cursor-grabbing p-2 text-gray-400 hover:text-gray-600">
                                                <Bars3Icon class="w-5 h-5" />
                                            </div>

                                            <div class="flex-1 ml-3">
                                                <h4 class="font-medium text-gray-900">{{ lp.product?.name }}</h4>
                                                <p class="text-sm text-gray-500">SKU: {{ lp.product?.sku }}</p>
                                            </div>

                                            <div class="flex items-center space-x-2">
                                                <button
                                                    @click="toggleProduct(lp)"
                                                    class="p-2 rounded-lg transition-colors"
                                                    :class="lp.is_active ? 'text-green-600 hover:bg-green-50' : 'text-gray-400 hover:bg-gray-200'"
                                                >
                                                    <EyeIcon v-if="lp.is_active" class="w-5 h-5" />
                                                    <EyeSlashIcon v-else class="w-5 h-5" />
                                                </button>
                                                <button
                                                    @click="removeProduct(lp)"
                                                    class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                                >
                                                    <TrashIcon class="w-5 h-5" />
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </draggable>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </StoreSettingsTabs>
    </AdminLayout>
</template>