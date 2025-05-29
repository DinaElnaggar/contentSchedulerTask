<template>
  <div class="min-h-screen bg-gray-100 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-white shadow rounded-lg p-6">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-semibold text-gray-900">
            {{ isEditing ? 'Edit Post' : 'Create New Post' }}
          </h2>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-6">
          <!-- Title -->
          <div>
            <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
            <input
              type="text"
              id="title"
              v-model="form.title"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              required
            />
          </div>

          <!-- Content -->
          <div>
            <label for="content" class="block text-sm font-medium text-gray-700">Content</label>
            <textarea
              id="content"
              v-model="form.content"
              rows="4"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              required
            ></textarea>
          </div>

          <!-- Image Upload -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Image</label>
            <div class="mt-1 flex items-center">
              <input
                type="file"
                @change="handleImageUpload"
                accept="image/*"
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
              />
            </div>
            <div v-if="form.image" class="mt-2">
              <img :src="form.image" alt="Preview" class="h-32 w-32 object-cover rounded-lg" />
            </div>
          </div>

          <!-- Platforms -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Platforms</label>
            <div class="mt-2 grid grid-cols-2 gap-4">
              <div
                v-for="platform in platforms"
                :key="platform.id"
                class="relative flex items-center"
              >
                <input
                  type="checkbox"
                  :id="'platform-' + platform.id"
                  v-model="form.platforms"
                  :value="platform.id"
                  class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <label :for="'platform-' + platform.id" class="ml-3 block text-sm font-medium text-gray-700">
                  {{ platform.name }}
                </label>
              </div>
            </div>
          </div>

          <!-- Schedule -->
          <div>
            <label for="scheduled_at" class="block text-sm font-medium text-gray-700">Schedule Date & Time</label>
            <input
              type="datetime-local"
              id="scheduled_at"
              v-model="form.scheduled_at"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              required
            />
          </div>

          <!-- Error Message -->
          <div v-if="error" class="rounded-md bg-red-50 p-4">
            <div class="flex">
              <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">
                  {{ error }}
                </h3>
              </div>
            </div>
          </div>

          <!-- Submit Button -->
          <div class="flex justify-end">
            <button
              type="button"
              @click="$router.back()"
              class="mr-3 px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <svg
                v-if="loading"
                class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
              {{ loading ? 'Saving...' : (isEditing ? 'Update Post' : 'Create Post') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'

export default {
  name: 'PostEditor',
  setup() {
    const router = useRouter()
    const route = useRoute()
    const loading = ref(false)
    const error = ref('')
    const platforms = ref([])
    
    const form = reactive({
      title: '',
      content: '',
      image: '',
      platforms: [],
      scheduled_at: ''
    })

    const isEditing = computed(() => !!route.params.id)

    const fetchPlatforms = async () => {
      try {
        const response = await axios.get('/api/platforms')
        platforms.value = response.data
      } catch (e) {
        error.value = 'Error loading platforms'
      }
    }

    const fetchPost = async () => {
      if (!isEditing.value) return

      try {
        const response = await axios.get(`/api/posts/${route.params.id}`)
        const post = response.data
        form.title = post.title
        form.content = post.content
        form.image = post.image
        form.platforms = post.platforms.map(p => p.id)
        form.scheduled_at = post.scheduled_at
      } catch (e) {
        error.value = 'Error loading post'
        router.push('/')
      }
    }

    const handleImageUpload = (event) => {
      const file = event.target.files[0]
      if (!file) return

      const reader = new FileReader()
      reader.onload = (e) => {
        form.image = e.target.result
      }
      reader.readAsDataURL(file)
    }

    const handleSubmit = async () => {
      loading.value = true
      error.value = ''

      try {
        const payload = {
          title: form.title,
          content: form.content,
          image: form.image,
          platforms: form.platforms,
          scheduled_at: form.scheduled_at
        }

        if (isEditing.value) {
          await axios.put(`/api/posts/${route.params.id}`, payload)
        } else {
          await axios.post('/api/posts', payload)
        }

        router.push('/')
      } catch (e) {
        error.value = e.response?.data?.message || 'Error saving post'
      } finally {
        loading.value = false
      }
    }

    onMounted(() => {
      fetchPlatforms()
      fetchPost()
    })

    return {
      form,
      loading,
      error,
      platforms,
      isEditing,
      handleImageUpload,
      handleSubmit
    }
  }
}
</script> 