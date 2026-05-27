import express from 'express'
import cors from 'cors'
import sqlite3 from 'sqlite3'
import { open } from 'sqlite'
import tutorsRouter from './routes/tutors.js'
import postsRouter from './routes/posts.js'

const app = express()
const PORT = 3000
app.use(cors())
app.use(express.json())

const db = await open({ filename: './data/tutor.db', driver: sqlite3.Database })

await db.exec(`
CREATE TABLE IF NOT EXISTS tutors (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  subject TEXT NOT NULL,
  region TEXT NOT NULL,
  gender TEXT NOT NULL,
  summary TEXT NOT NULL,
  rating REAL NOT NULL,
  price TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS posts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT NOT NULL,
  subject TEXT NOT NULL,
  region TEXT NOT NULL,
  budget TEXT NOT NULL,
  content TEXT NOT NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
`)

const row = await db.get('SELECT COUNT(*) as count FROM tutors')
if (row.count === 0) {
  const demo = [
    ['林老師','數學','台中','女','擅長國高中數學與段考衝刺。',4.9,'NT$700'],
    ['陳老師','英文','台北','男','口說與文法並重，風格耐心。',4.8,'NT$800'],
    ['黃老師','國文','高雄','女','作文與閱讀理解強化。',4.7,'NT$650']
  ]
  for (const t of demo) await db.run(
    'INSERT INTO tutors (name,subject,region,gender,summary,rating,price) VALUES (?,?,?,?,?,?,?)',
    t
  )
}

app.use((req,res,next) => { req.db = db; next() })
app.use('/api/tutors', tutorsRouter)
app.use('/api/posts', postsRouter)

app.get('/api/health', (req,res) => res.json({ ok: true }))

app.listen(PORT, () => console.log(`API running on http://localhost:${PORT}`))