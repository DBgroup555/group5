<template>
  <div class="app">
    <header class="topbar">
      <button class="menu-btn" @click="sidebarOpen = !sidebarOpen">☰</button>
      <div class="brand">家教媒合平台</div>
      <div class="searchbar">
        <input v-model="keyword" placeholder="搜尋科目、地區、老師" />
        <button class="primary" @click="loadTutors">搜尋</button>
      </div>
      <div class="actions">
        <button @click="page='login'">登入</button>
        <button @click="page='register'">註冊</button>
      </div>
    </header>

    <main class="layout">
      <aside class="sidebar card" :class="{ open: sidebarOpen }">
        <h2>多條件搜尋</h2>
        <button class="close-btn" @click="sidebarOpen = false">×</button>
        <label>科目<select v-model="filters.subject" @change="loadTutors"><option>全部</option><option>數學</option><option>英文</option><option>國文</option><option>理化</option></select></label>
        <label>地區<select v-model="filters.region" @change="loadTutors"><option>全部</option><option>台北</option><option>台中</option><option>高雄</option></select></label>
        <label>性別<select v-model="filters.gender" @change="loadTutors"><option>全部</option><option>男</option><option>女</option></select></label>
        <button class="primary" @click="loadTutors">套用篩選</button>
      </aside>

      <section class="main">
        <section class="hero card">
          <div>
            <h1>找到合適的家教，或發布你的需求</h1>
            <p>Vue 前端已串接後端 API，可以真的抓資料。</p>
          </div>
          <div class="hero-actions">
            <button class="primary" @click="page='post'">發布家教需求</button>
            <button @click="loadPosts">查看需求貼文</button>
          </div>
        </section>

        <section class="section-head">
          <h2>推薦家教</h2>
          <span>共 {{ tutors.length }} 位</span>
        </section>

        <section class="cards">
          <article v-for="tutor in tutors" :key="tutor.id" class="tutor card" @click="selectedTutor=tutor">
            <div class="avatar"></div>
            <h3>{{ tutor.name }}</h3>
            <p>{{ tutor.subject }} · {{ tutor.region }} · {{ tutor.gender }}</p>
            <p class="desc">{{ tutor.summary }}</p>
            <div class="meta"><span>⭐ {{ tutor.rating }}</span><span>{{ tutor.price }} / hr</span></div>
            <button class="primary" @click.stop="selectedTutor=tutor">查看詳情</button>
          </article>
        </section>

        <section v-if="page==='post'" class="card formcard">
          <h2>發布家教需求</h2>
          <div class="formgrid">
            <div class="field"><label>標題</label><input v-model="postForm.title" placeholder="例如：國中數學家教" /></div>
            <div class="field"><label>科目</label><input v-model="postForm.subject" placeholder="例如：數學" /></div>
            <div class="field"><label>地區</label><input v-model="postForm.region" placeholder="例如：台中" /></div>
            <div class="field"><label>預算</label><input v-model="postForm.budget" placeholder="例如：NT$700/hr" /></div>
            <div class="field full"><label>需求內容</label><textarea v-model="postForm.content" rows="4" placeholder="請描述學生年級、程度、上課方式、時間等"></textarea></div>
          </div>
          <div class="btnrow"><button class="primary" @click="createPost">送出需求</button><button @click="page='home'">取消</button></div>
        </section>

        <section v-if="page==='posts'" class="card formcard">
          <h2>需求貼文</h2>
          <div class="postlist">
            <div v-for="post in posts" :key="post.id" class="postitem">
              <strong>{{ post.title }}</strong>
              <p>{{ post.subject }} · {{ post.region }} · {{ post.budget }}</p>
              <p class="desc">{{ post.content }}</p>
            </div>
          </div>
          <button @click="page='home'">回首頁</button>
        </section>
      </section>

      <aside class="sidebar card">
        <h2>功能入口</h2>
        <button @click="page='settings'">帳號與設定</button>
        <button @click="page='post'">發布需求</button>
        <button @click="page='applications'">應徵清單</button>
        <button @click="page='reviews'">公開評價</button>
        <button @click="page='chat'">聊天室</button>
        <button @click="page='posts'; loadPosts()">需求貼文</button>
      </aside>
    </main>

    <div v-if="selectedTutor" class="modal-backdrop" @click.self="selectedTutor=null">
      <div class="modal card">
        <button class="close" @click="selectedTutor=null">×</button>
        <div class="avatar large"></div>
        <h2>{{ selectedTutor.name }}</h2>
        <p>{{ selectedTutor.subject }} · {{ selectedTutor.region }} · {{ selectedTutor.gender }}</p>
        <p class="desc">{{ selectedTutor.summary }}</p>
        <div class="meta"><span>⭐ {{ selectedTutor.rating }}</span><span>{{ selectedTutor.price }} / hr</span></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'

const API = 'http://localhost:3000/api'
const keyword = ref('')
const page = ref('home')
const tutors = ref([])
const posts = ref([])
const selectedTutor = ref(null)
const filters = reactive({ subject: '全部', region: '全部', gender: '全部' })
const postForm = reactive({ title: '', subject: '', region: '', budget: '', content: '' })
const sidebarOpen = ref(false)

async function loadTutors() {
  const params = new URLSearchParams()
  if (keyword.value.trim()) params.set('q', keyword.value.trim())
  if (filters.subject !== '全部') params.set('subject', filters.subject)
  if (filters.region !== '全部') params.set('region', filters.region)
  if (filters.gender !== '全部') params.set('gender', filters.gender)

  const res = await fetch(`${API}/tutors?${params.toString()}`)
  tutors.value = await res.json()
}

async function loadPosts() {
  const res = await fetch(`${API}/posts`)
  posts.value = await res.json()
  page.value = 'posts'
}

async function createPost() {
  const res = await fetch(`${API}/posts`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(postForm)
  })
  if (res.ok) {
    Object.assign(postForm, { title: '', subject: '', region: '', budget: '', content: '' })
    await loadPosts()
    page.value = 'posts'
  }
}

onMounted(loadTutors)
</script>