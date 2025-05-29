<template>
  <div class="min-h-screen bg-gray-100">
    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
          <h1 class="text-2xl font-semibold text-gray-900">Content Schedule</h1>
          <div class="flex space-x-4">
            <button
              @click="viewMode = 'calendar'"
              :class="[
                'px-4 py-2 rounded-md text-sm font-medium',
                viewMode === 'calendar'
                  ? 'bg-indigo-600 text-white'
                  : 'bg-white text-gray-700 hover:bg-gray-50'
              ]"
            >
              Calendar View
            </button>
            <button
              @click="viewMode = 'list'"
              :class="[
                'px-4 py-2 rounded-md text-sm font-medium',
                viewMode === 'list'
                  ? 'bg-indigo-600 text-white'
                  : 'bg-white text-gray-700 hover:bg-gray-50'
              ]"
            >
              List View
            </button>
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="mt-4 bg-red-50 border border-red-200 text-red-800 rounded-lg p-4">
          {{ error }}
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="mt-4 flex justify-center">
          <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </div>

        <!-- Content when not loading -->
        <div v-else>
          <!-- Filters -->
          <div class="mt-4 bg-white shadow rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Platform</label>
                <select
                  v-model="filters.platform"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                  <option value="">All Platforms</option>
                  <option v-for="platform in platforms" :key="platform.id" :value="platform.id">
                    {{ platform.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Status</label>
                <select
                  v-model="filters.status"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                  <option value="">All Status</option>
                  <option value="scheduled">Scheduled</option>
                  <option value="published">Published</option>
                  <option value="failed">Failed</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Date Range</label>
                <input
                  type="date"
                  v-model="filters.startDate"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>
              <div class="flex items-end">
                <button
                  @click="applyFilters"
                  class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                  Apply Filters
                </button>
              </div>
            </div>
          </div>

          <!-- No Data Message -->
          <div v-if="!loading && (!posts || posts.length === 0)" class="mt-4 bg-white shadow rounded-lg p-8 text-center">
            <p class="text-gray-500">No posts found. Create your first post to get started!</p>
            <button
              @click="router.push('/post/create')"
              class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Create Post
            </button>
          </div>

          <!-- Calendar View -->
          <div v-else-if="viewMode === 'calendar'" class="mt-4">
            <div class="bg-white shadow rounded-lg p-4">
              <div class="grid grid-cols-7 gap-px border-b border-gray-300">
                <div
                  v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']"
                  :key="day"
                  class="text-center py-2 font-semibold text-gray-700"
                >
                  {{ day }}
                </div>
              </div>
              <div class="grid grid-cols-7 gap-px">
                <div
                  v-for="date in calendarDates"
                  :key="date.date"
                  class="min-h-32 p-2 border-b border-r border-gray-200"
                  :class="{
                    'bg-gray-50': !date.isCurrentMonth,
                    'bg-white': date.isCurrentMonth
                  }"
                >
                  <div class="font-medium text-sm text-gray-700">
                    {{ new Date(date.date).getDate() }}
                  </div>
                  <div class="mt-2 space-y-1">
                    <div
                      v-for="post in getPostsForDate(date.date)"
                      :key="post.id"
                      class="text-sm p-1 rounded-md cursor-pointer"
                      :class="{
                        'bg-blue-100 text-blue-700': post.status === 'scheduled',
                        'bg-green-100 text-green-700': post.status === 'published',
                        'bg-red-100 text-red-700': post.status === 'failed'
                      }"
                      @click="editPost(post)"
                    >
                      {{ post.title }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- List View -->
          <div v-else class="mt-4">
            <div class="bg-white shadow rounded-lg">
              <table class="min-w-full divide-y divide-gray-200">
                <thead>
                  <tr>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Title
                    </th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Platform
                    </th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Schedule Date
                    </th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Status
                    </th>
                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="post in filteredPosts" :key="post.id">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ post.title }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ getPlatformName(post.platform_id) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ formatDate(post.scheduled_at) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span
                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                        :class="{
                          'bg-blue-100 text-blue-800': post.status === 'scheduled',
                          'bg-green-100 text-green-800': post.status === 'published',
                          'bg-red-100 text-red-800': post.status === 'failed'
                        }"
                      >
                        {{ post.status }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <button
                        @click="editPost(post)"
                        class="text-indigo-600 hover:text-indigo-900 mr-4"
                      >
                        Edit
                      </button>
                      <button
                        @click="deletePost(post)"
                        class="text-red-600 hover:text-red-900"
                      >
                        Delete
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { format, startOfMonth, endOfMonth, eachDayOfInterval, isSameMonth } from 'date-fns'
import axios from 'axios'

export default {
  name: 'Dashboard',
  setup() {
    const router = useRouter()
    const viewMode = ref('calendar')
    const currentDate = ref(new Date())
    const posts = ref([])
    const platforms = ref([])
    const loading = ref(false)
    const error = ref('')
    const filters = reactive({
      platform: '',
      status: '',
      startDate: '',
      endDate: ''
    })

    const fetchPosts = async () => {
      try {
        loading.value = true
        error.value = ''
        const response = await axios.get('/api/posts', {
          params: {
            platform: filters.platform,
            status: filters.status,
            start_date: filters.startDate,
            end_date: filters.endDate
          }
        })
        posts.value = response.data.data
      } catch (e) {
        error.value = e.response?.data?.message || 'Error fetching posts'
        console.error('Error fetching posts:', e)
      } finally {
        loading.value = false
      }
    }

    const fetchPlatforms = async () => {
      try {
        const response = await axios.get('/api/platforms')
        platforms.value = response.data.data.platforms
      } catch (e) {
        error.value = e.response?.data?.message || 'Error fetching platforms'
        console.error('Error fetching platforms:', e)
      }
    }

    const calendarDates = computed(() => {
      const start = startOfMonth(currentDate.value)
      const end = endOfMonth(currentDate.value)
      return eachDayOfInterval({ start, end }).map(date => ({
        date: date,
        isCurrentMonth: isSameMonth(date, currentDate.value)
      }))
    })

    const getPostsForDate = (date) => {
      return posts.value.filter(post => {
        const postDate = new Date(post.scheduled_at)
        return format(postDate, 'yyyy-MM-dd') === format(date, 'yyyy-MM-dd')
      })
    }

    const filteredPosts = computed(() => {
      return posts.value.filter(post => {
        if (filters.platform && post.platform_id !== filters.platform) return false
        if (filters.status && post.status !== filters.status) return false
        if (filters.startDate) {
          const postDate = new Date(post.scheduled_at)
          const filterDate = new Date(filters.startDate)
          if (postDate < filterDate) return false
        }
        return true
      })
    })

    const formatDate = (date) => {
      return format(new Date(date), 'MMM dd, yyyy HH:mm')
    }

    const getPlatformName = (platformId) => {
      const platform = platforms.value.find(p => p.id === platformId)
      return platform ? platform.name : ''
    }

    const editPost = (post) => {
      router.push(`/post/${post.id}/edit`)
    }

    const deletePost = async (post) => {
      if (!confirm('Are you sure you want to delete this post?')) return
      
      try {
        loading.value = true
        error.value = ''
        await axios.delete(`/api/posts/${post.id}`)
        await fetchPosts()
      } catch (e) {
        error.value = e.response?.data?.message || 'Error deleting post'
        console.error('Error deleting post:', e)
      } finally {
        loading.value = false
      }
    }

    const applyFilters = () => {
      fetchPosts()
    }

    onMounted(() => {
      // Ensure the token is set in axios headers
      const token = localStorage.getItem('token')
      if (token) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
      }
      
      fetchPosts()
      fetchPlatforms()
    })

    return {
      viewMode,
      posts,
      platforms,
      filters,
      loading,
      error,
      calendarDates,
      filteredPosts,
      getPostsForDate,
      formatDate,
      getPlatformName,
      editPost,
      deletePost,
      applyFilters
    }
  }
}
</script> 