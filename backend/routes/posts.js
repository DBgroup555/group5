import { Router } from 'express'
const router = Router()

router.get('/', async (req, res) => {
  const rows = await req.db.all('SELECT * FROM posts ORDER BY id DESC')
  res.json(rows)
})

router.post('/', async (req, res) => {
  const { title, subject, region, budget, content } = req.body
  if (!title || !subject || !region || !budget || !content) {
    return res.status(400).json({ message: 'missing fields' })
  }

  const result = await req.db.run(
    'INSERT INTO posts (title, subject, region, budget, content) VALUES (?,?,?,?,?)',
    [title, subject, region, budget, content]
  )
  const created = await req.db.get('SELECT * FROM posts WHERE id = ?', [result.lastID])
  res.status(201).json(created)
})

export default router