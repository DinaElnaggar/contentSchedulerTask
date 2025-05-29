<template>
  <div class="min-h-screen bg-gray-100 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-semibold text-gray-900">Platform Settings</h2>
        </div>

        <!-- Platform List -->
        <div class="space-y-6">
          <div v-for="platform in platforms" :key="platform.id" class="border rounded-lg p-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <h3 class="text-lg font-medium text-gray-900">{{ platform.name }}</h3>
                <span
                  :class="[
                    'ml-3 px-2 py-1 text-xs font-medium rounded-full',
                    platform.is_active
                      ? 'bg-green-100 text-green-800'
                      : 'bg-gray-100 text-gray-800'
                  ]"
                >
                  {{ platform.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
              <button
                @click="togglePlatform(platform)"
                :disabled="loading"
                class="ml-4 px-3 py-1 text-sm font-medium rounded-md"
                :class="[
                  platform.is_active
                    ? 'text-red-700 bg-red-100 hover:bg-red-200'
                    : 'text-green-700 bg-green-100 hover:bg-green-200'
                ]"
              >
                {{ platform.is_active ? 'Deactivate' : 'Activate' }}
              </button>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4">
              <div v-for="(value, key) in platform.credentials" :key="key" class="flex items-center">
                <label :for="key" class="block text-sm font-medium text-gray-700 w-1/4">
                  {{ formatCredentialKey(key) }}
                </label>
                <div class="mt-1 w-3/4">
                  <input
                    :type="key.toLowerCase().includes('token') || key.toLowerCase().includes('secret') ? 'password' : 'text'"
                    :id="key"
                    v-model="platform.credentials[key]"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  />
                </div>
              </div>
            </div>

            <div class="mt-4 flex justify-end">
              <button
                @click="updatePlatform(platform)"
                :disabled="loading"
                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                Save Changes
              </button>
            </div>
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="mt-6 rounded-md bg-red-50 p-4">
          <div class="flex">
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800">
                {{ error }}
              </h3>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import axios from 'axios'

export default {
  name: 'Settings',
  setup() {
    const platforms = ref([])
    const loading = ref(false)
    const error = ref('')

    const fetchPlatforms = async () => {
      try {
        const response = await axios.get('/api/platforms')
        platforms.value = response.data
      } catch (e) {
        error.value = 'Error loading platforms'
      }
    }

    const togglePlatform = async (platform) => {
      loading.value = true
      error.value = ''

      try {
        await axios.post(`/api/platforms/${platform.id}/toggle`)
        platform.is_active = !platform.is_active
      } catch (e) {
        error.value = e.response?.data?.message || 'Error toggling platform'
      } finally {
        loading.value = false
      }
    }

    const updatePlatform = async (platform) => {
      loading.value = true
      error.value = ''

      try {
        await axios.put(`/api/platforms/${platform.id}`, {
          credentials: platform.credentials
        })
      } catch (e) {
        error.value = e.response?.data?.message || 'Error updating platform'
      } finally {
        loading.value = false
      }
    }

    const formatCredentialKey = (key) => {
      return key
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ')
    }

    onMounted(fetchPlatforms)

    return {
      platforms,
      loading,
      error,
      togglePlatform,
      updatePlatform,
      formatCredentialKey
    }
  }
}
</script> 