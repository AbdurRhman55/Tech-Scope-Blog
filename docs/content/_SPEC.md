# TechScope Deep-Content Expansion — Writer Spec (v1)

**Mission:** expand thin ~100-word stub articles into genuinely deep, professional, useful
articles — the kind a working practitioner would bookmark. Depth = usefulness, not padding.

You are writing for **developers, technology learners, and technical professionals**.
Voice: confident editorial expert (see existing articles for tone); English; US spelling unless
the article uses another convention.

---

## 1. Output contract

- Write the FULL article body to `docs/content/<slug>.html` (one file per article).
- **Do NOT include an `<h1>`** — the page template renders the title.
- Start with the intro `<p>` (rewrite the original intro if you can do it better — same promise).
- The file will be sanitized against the allowlist in §3 and applied to the DB by a review
  step. **Never edit the database, never edit site PHP/CSS, never touch other folders.**
- Only files you create: `docs/content/<slug>.html` and `assets/img/diagrams/*.svg`.

## 2. Target shape (per article)

- **1,500–2,600 words** of real content (currently ~100–200 — the whole point of this task).
- **6–12 `<h2>` sections**, `<h3>`/`<h4>` where a section has sub-parts. Headings describe
  real content (no keyword stuffing), in logical order.
- Required ingredients (adapt to intent — see `docs/EDITORIAL_PLAYBOOK.md` §2–4):
  - Direct, useful answer near the top (first 2 paragraphs after intro).
  - Generous **bullet points and numbered lists** — the owner explicitly wants many points.
  - At least **1 table** where a table genuinely helps (comparison, parameters, checklist).
  - At least **1 practical example**: worked example, realistic scenario, or code block
    (`<pre><code>` — wrap with language-free markup, the theme styles it).
  - Edge cases, common mistakes, limitations — a section that honestly says when NOT to use/do.
  - A "Key takeaways" `<ul>` section before the closing paragraph.
  - Concrete next step in the conclusion.
- Paragraphs: 2–5 sentences, readable; no filler, no repetition of the same idea twice.
- Keep the article's original premise, title meaning, and category angle. You are *deepening*,
  not changing the thesis.

## 3. HTML allowlist (exactly what survives sanitizing)

`p br h2 h3 h4 strong b em i u s blockquote ul ol li a pre code hr img span figure figcaption table thead tbody tr th td`

- **No `<div>`, no `<svg>` inline, no classes/ids needed** (theme styles prose automatically).
- Tables: full `table > thead > tbody > tr > th/td` structure with header row.
- Code: `<pre><code>...</code></pre>`; escape `<` `>` `&` inside code.
- Images: only local originals you create: `<figure><img src="/assets/img/diagrams/<file>.svg" alt="…"><figcaption>…</figcaption></figure>`
- Links: `<a href="/post/<slug>">` for internal articles; external `<a href="https://…">` only
  for official documentation (§4). Add `rel="external noopener"` on external links.

## 4. Accuracy & originality — HARD RULES (violating these = rejected output)

1. **No invented statistics.** Never write "studies show 47%", "increases retention by 3×",
   benchmark numbers, percentages or prices unless it is uncontroversial common knowledge
   (e.g. "60 seconds in a minute"). If a number matters, either cite an official source URL
   you are **certain** exists or present it as a clearly-labeled illustrative example
   ("say your retention is 40%…").
2. **No fabricated studies, experts, quotes, surveys, or case studies.** Never attribute a
   claim to a named person/organisation you are not sure about. Blockquotes must be the
   article's own framing statements, not fake quotations.
3. **No claim that code was executed** unless you phrase examples as illustrative
   ("example output", "typical plan"). Never "we benchmarked/run/tested".
4. **Cite only well-known official documentation** when a link genuinely helps, e.g.
   `https://dev.mysql.com/doc/refman/8.4/en/…`, `https://www.php.net/…`,
   `https://developer.mozilla.org/en-US/docs/…`, `https://docs.python.org/3/…`,
   `https://git-scm.com/doc`, `https://developer.mozilla.org/en-US/docs/Web/HTTP/…`.
   **If unsure the exact URL exists — do not link it.** Zero external links is acceptable.
5. **No fabricated screenshots or fake UI.** Visuals = hand-drawn diagram SVGs you create
   (original artwork, accurate to the concept) or honest illustrative output blocks.
6. Fast-moving claims (AI models, hardware, browser support, cloud prices) get an
   "as of October 2026" style date stamp, or are phrased as durable principles.
7. Do not copy wording from any external article. Everything original.
8. Do not promise rankings, career outcomes, or guaranteed results.

## 5. Internal linking (SEO system adds more automatically — yours must be valid)

Only these published slugs may be linked (all relative):

```
how-large-language-models-actually-work, rag-vs-fine-tuning-llm, context-engineering-over-prompt-engineering,
local-llms-laptop-guide, clean-code-php-patterns, git-workflows-team-scale, typescript-tips-hard-way,
sql-indexing-practical-guide, data-visualization-principles, cohort-analysis-retention-guide,
feature-engineering-tabular-data, serverless-architecture-when-it-shines-and-when-it-fails,
how-to-read-research-paper, reproducible-research-environment, evidence-based-learning-techniques,
student-portfolio-gets-interviews, interview-prep-star-method, freelance-career-pricing-clients-burnout,
freelance-niche-positioning, client-onboarding-emails-scope-creep, deep-work-realistic-system,
zettelkasten-note-taking-simple, developer-hardware-guide-2026, passkeys-passwordless-web
```

- Add 2–4 genuinely-relevant contextual links per article with descriptive anchors
  (e.g. `<a href="/post/sql-indexing-practical-guide">reading a query plan</a>`).
- Never link a page not in this list. Never link the article to itself.

## 6. Diagrams (optional but encouraged — max 1–2 per article)

- Hand-write an SVG to `assets/img/diagrams/<slug>-<topic>.svg` (e.g. `…-llm-flow.svg`).
- Style: 900×520-ish viewBox, white/`#f7f6f2` background, ink `#14161c`, primary `#1f4fd8`,
  accent `#e8402a`, muted text `#5a6070`; boxes = rounded rects with 15–17px labels; arrows
  with `marker-end`; clean sans-serif (`font-family="Manrope, Arial, sans-serif"`).
- Accurate to the concept it illustrates — it teaches, it does not decorate.
- Reference it in HTML exactly:
  `<figure><img src="/assets/img/diagrams/<file>.svg" alt="Accurate alt text describing the diagram"><figcaption>Short caption</figcaption></figure>`

## 7. Research workflow per article

1. Read the original: `docs/content/_current/<slug>.html` (its promise must survive).
2. Read its audit card in `docs/CONTENT_INVENTORY.md` (audience, intent, ⚠-flags — honour them).
3. Read `docs/EDITORIAL_PLAYBOOK.md` §2–5 for structure/depth/quality bars.
4. Draft the full file to `docs/content/<slug>.html`.
5. Self-check against §2 and §4 before finishing.

**Never delete or contradict a fact from the original without marking it in your final report.**
