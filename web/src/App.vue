<script setup lang="ts">
import { onMounted, ref } from 'vue'

type Health = { status: string; db: string }

const health = ref<Health | null>(null)
const error = ref('')

onMounted(async () => {
  try {
    const res = await fetch('/api/health')
    health.value = (await res.json()) as Health
  } catch {
    error.value = 'サーバーに接続できません'
  }
})
</script>

<template>
  <main>
    <h1>ソムリエ試験対策</h1>
    <p v-if="error">{{ error }}</p>
    <p v-else-if="health">サーバー: {{ health.status }} / DB: {{ health.db }}</p>
    <p v-else>確認中…</p>
  </main>
</template>
