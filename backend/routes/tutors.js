import { Router } from 'express'
const router = Router()

router.get('/', async (req, res) => {
  const { subject = '', region = '', gender = '', q = '' } = req.query
  const clauses = []
  const params = []

  if (subject && subject !== '全部') {
    clauses.push('subject = ?')
    params.push(subject)
  }
  if (region && region !== '全部') {
    clauses.push('region = ?')
    params.push(region)
  }
  if (gender && gender !== '全部') {
    clauses.push('gender = ?')
    params.push(gender)
  }
  if (q) {
    clauses.push('(name LIKE ? OR summary LIKE ? OR subject LIKE ? OR region LIKE ?)')
    params.push(`%${q}%`, `%${q}%`, `%${q}%`, `%${q}%`)
  }

  const where = clauses.length ? `WHERE ${clauses.join(' AND ')}` : ''
  const rows = await req.db.all(`SELECT * FROM tutors ${where} ORDER BY id DESC`, params)
  res.json(rows)
})

router.get('/:id', async (req, res) => {
  const row = await req.db.get('SELECT * FROM tutors WHERE id = ?', [req.params.id])
  if (!row) return res.status(404).json({ message: 'not found' })
  res.json(row)
})

export default router