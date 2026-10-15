# TechScope — Topical Cluster Architecture & Pillar Map (Parts B, D, G)

**Date:** 2026-10-12 · **Built from:** verified database content (see `CONTENT_INVENTORY.md`).
**Rule applied:** clusters validate against real articles — no taxonomy was added, removed, or
renamed; no page is proposed purely to complete a diagram; no links are planned to unpublished pages.

---

## 1. Validation of the proposed model against real content (Part B)

The brief proposed 8 clusters. Inspecting all 25 posts validates 8 of them directly and requires
one adjustment:

| Proposed cluster | Verdict | Evidence (verified) | Adjustment |
|---|---|---|---|
| 1. Artificial Intelligence | ✅ Valid | Category *Artificial Intelligence*: #1, #8, #9, #24 | — |
| 2. Programming & Software Engineering | ✅ Valid | Category *Programming*: #6, #14, #15 | — |
| 3. Data Science & Analytics | ✅ Valid | Category *Data Science*: #2, #5, #12, #13 | — |
| 4. Cloud Computing & Infrastructure | ✅ Valid (thin) | Category *Cloud Computing*: #7 only (owner-created) | Keep; build to ≥5 before considering taxonomy changes |
| 5. Research & Technical Evaluation | ✅ Valid | Category *Research*: #16, #17 | Stays connected to tech/data (both articles are tech-adjacent) |
| 6. Student & Career Development | ✅ Valid | Category *Student & Career*: #4, #18, #19 | — |
| 7. Technology Freelancing | ✅ Valid | Category *Freelancing*: #3, #22, #23 | — |
| 8. Productivity & Developer Workflows | ✅ Valid | Category *Productivity*: #20, #21 + draft #25 | Knowledge-worker focus kept; no lifestyle topics |
| *(unmapped)* | ⚠ Needs a home | Category *Technology*: #10 (hardware), #11 (passkeys) fit none of the 8 | **Added as Cluster 9 — "Technology, Hardware & Digital Security"** |

**Why Cluster 9 instead of folding those two articles elsewhere:** hardware buying (#10) is not
programming and not AI; passkeys (#11) is web security, not cloud infrastructure. Forcing either
into another cluster would distort it. The alternative (merging *Technology* into *Programming*)
is documented as an option for the site owner but not implemented — 1 cluster ↔ 1 existing
category is the least invasive architecture and honors "avoid unnecessary taxonomy expansion".

**Positioning check:** all 9 clusters serve developers, technology learners, and technical
professionals — no general-interest drift.

---

## 2. Final architecture (9 clusters)

| # | Cluster | Audience | Core subject areas (validated) | Home category |
|---|---|---|---|---|
| C1 | Artificial Intelligence | Devs & practitioners adopting AI | LLM internals, tokens/embeddings/attention, prompting & context, RAG, fine-tuning, local LLMs & quantization, evaluation, reliability | Artificial Intelligence |
| C2 | Programming & Software Engineering | Working developers | PHP/web, Git & collaboration, testing/debugging, clean code, architecture, API & app security, performance | Programming |
| C3 | Data Science & Analytics | Analysts & data scientists | SQL performance, analysis methods, feature engineering, visualization, cohorts/analytics, reproducibility (with C5) | Data Science |
| C4 | Cloud Computing & Infrastructure | Architects & backend devs | Serverless trade-offs, deployment, containers-where-relevant, monitoring, reliability, cost control | Cloud Computing |
| C5 | Research & Technical Evaluation | Technical readers evaluating evidence | Reading papers, reproducible environments, benchmark methodology, model/system evaluation | Research |
| C6 | Student & Career Development | Students & junior devs | Study methods, portfolios, interviews, learning roadmaps, technical communication | Student & Career |
| C7 | Technology Freelancing | Developer-freelancers | Positioning, pricing, onboarding, scoping, proposals, client communication | Freelancing |
| C8 | Productivity & Developer Workflows | Knowledge workers/developers | Focus systems, note-taking/PKMs, context switching, automation & dev tools | Productivity |
| C9 | Technology, Hardware & Digital Security | Developers buying/securing machines | Hardware decisions, passkeys/passwordless, account security | Technology |

---

## 3. Pillar assessment (Part D.1–D.4)

| Cluster | Existing pillar candidate | Sufficient today? | Plan |
|---|---|---|---|
| C1 | **#1 LLM guide** (209w) | ❌ No — too thin to organize the cluster | **Expand #1** into *A Practical Guide to Large Language Models* (tokens→embeddings→attention→evaluation), 1,200+ words with a flow diagram |
| C1 | #8 RAG vs FT, #24 Local LLMs | ❌ Both thin | Treat as **comparison/evaluation sub-pillars**; expand each (decision table; hardware/VRAM table) |
| C2 | **#6 Clean Code PHP** (167w) | ❌ No single organizing page | **Expand #6** toward *Maintainable PHP* pillar; support with testing/debugging/env-config gap articles |
| C2 | #14 Git workflows | ✅ Fits its own intent | Expand with command examples; `merge vs rebase` = separate page (distinct intent) |
| C3 | **#2 SQL indexing** (154w) | ❌ No | **Expand #2** into *Practical SQL Performance Optimization* (+ EXPLAIN gap article). ⚠ Title "100x" flag first |
| C3 | #13 Feature engineering | ⚠ Near-pillar | Expand only if capacity; clusters must not grow filler |
| C4 | **#7 Serverless** (31w) | ❌ Critical | Publish reviewed expansion draft → *Serverless Architecture: A Practical Decision Guide* |
| C5 | none needed | — | Two self-contained method pages; add *benchmark methodology* when capacity allows |
| C6 | none needed | — | Three distinct intents (study / portfolio / interview); no forced pillar |
| C7 | #3 as natural anchor | ⚠ Anchor, not pillar | Expand #3; supporting pages only where intent is distinct |
| C8 | none needed | — | Distinct how-tos; draft #25 is a companion, not a pillar |
| C9 | none needed | — | Two distinct pages; build only with real security/hardware depth |

**Rule:** a pillar exists to organize *meaningful* supporting content — none of the above
guarantees the proposed supporting articles; each must pass the gap test in §5 before writing.

---

## 4. Cluster map (Part D.6–D.8)

Legend — **Existing** = verified article; **Proposed** = brief-stage only (nothing linked yet).

### C1 Artificial Intelligence
- **Pillar:** #1 *A Practical Guide to LLMs* (expand) — intent: informational how-it-works.
- **Existing supporting:** #8 (comparison: RAG vs FT) · #9 (paradigm: context engineering) · #24 (tutorial/evaluation: local LLMs).
- **Missing supporting (validated, priority order):** How tokenization works (informational) ·
  Embeddings explained with examples (informational) · How vector search works (how-to) ·
  How to evaluate LLM responses (method) · Common RAG retrieval failures (troubleshooting) ·
  Quantization & model memory (reference/tutorial).
- **Links:** pillar↔#8/#9/#24 both directions (expand auto-map after each expansion); #24↔#10 (hardware).

### C2 Programming & Software Engineering
- **Pillar:** #6 *Maintainable PHP* (expand) — intent: educational.
- **Existing supporting:** #14 (decision: workflows at scale) · #15 (tips: TypeScript pitfalls).
- **Missing:** Testing PHP: a practical first suite (tutorial) · Debugging common PHP errors
  (troubleshooting) · Managing environment configuration safely (how-to) · Git merge vs rebase
  (comparison) · How to review pull requests (how-to) · Reading query plans (EXPLAIN) — shared with C3.
- **Links:** pillar↔#14/#15; #6↔#2 (prepared statements); testing article ↔ pillar.

### C3 Data Science & Analytics
- **Pillar:** #2 *Practical SQL Performance Optimization* (expand after ⚠ title review).
- **Existing supporting:** #13 (argument: feature engineering) · #12 (tutorial: cohorts) · #5 (principles: visualization).
- **Missing:** Reading EXPLAIN output (reference) · Experiment design basics (method) ·
  Data quality checks (how-to). *(Defer "general data analysis pillar" — current articles cover
  distinct methods; adding a broad page now risks a thin page.)*
- **Links:** pillar↔#13/#12/#5; #2↔#6; #12↔#17 (reproducibility).

### C4 Cloud Computing & Infrastructure
- **Pillar:** #7 *Serverless decision guide* (P0 expansion).
- **Existing supporting:** — (single article cluster).
- **Missing:** Application deployment fundamentals (tutorial) · Monitoring & reliability: what to
  alert on (how-to) · Container basics for web apps (informational) · Cloud cost control habits (guidance).
- **Links:** pillar↔each new article; #7↔#2 (performance angle). **No links to these until published.**

### C5 Research & Technical Evaluation
- **Pillar:** none (by design).
- **Existing:** #16 (method: reading papers) · #17 (tutorial: reproducible environments).
- **Missing:** Benchmark methodology: how to read a benchmark (informational) — connects to C1 evaluation.
- **Links:** #16↔#4 (study methods), #16↔#21 (notes), #17↔#12/#13.

### C6 Student & Career Development
- **Pillar:** none (three distinct intents).
- **Existing:** #4 (evidence review: study techniques) · #18 (how-to: portfolio) · #19 (how-to: STAR interviews).
- **Missing:** Developer learning roadmap (guidance) · Technical communication basics (how-to).
- **Links:** #18↔#19 (portfolio→interview journey), #4↔#16.

### C7 Technology Freelancing
- **Pillar:** #3 as anchor (overview page).
- **Existing:** #22 (strategy: positioning) · #23 (templates: onboarding).
- **Missing:** Project scoping & estimation (how-to) · Writing technical proposals (how-to) ·
  Requirements gathering for freelancers (method).
- **Links:** #3↔#22↔#23 (existing auto-map covers the trio's key phrases; editorial links in expansions).

### C8 Productivity & Developer Workflows
- **Pillar:** none (by design).
- **Existing:** #20 (system: deep work) · #21 (method: zettelkasten) · #25 draft (companion: context switching).
- **Missing:** Developer automation habits (how-to) · Technical note-taking tool comparisons (evaluation — only if genuinely distinct from #21).
- **Links:** #20↔#21↔#25; #20↔#4 (habits); #25↔#3 (burnout).

### C9 Technology, Hardware & Digital Security
- **Pillar:** none yet.
- **Existing:** #10 (evaluation: hardware 2026) · #11 (informational+guidance: passkeys).
- **Missing:** Rolling out 2FA without losing users (tutorial) · Account recovery when passkeys fail (troubleshooting) — both distinct from #11.
- **Links:** #10↔#24 (strongest cross-cluster pair), #11↔each future security page.

---

## 5. Search-intent distinctions & gap test (Part D.8)

Before writing any proposed page, it must pass:
1. **Distinct intent** from every existing page (compare intents, not title words — see inventory §3 for the verified no-overlap baseline);
2. **Answers a real reader question** a published article does *not* already answer;
3. **Has a genuine angle** (example, method, trade-off) — not a rephrasing;
4. **Fits cluster expertise** — else reject;
5. **Brief exists** (Playbook §1 fields) — else do not start drafting.

Re-check pairs (verified): *merge vs rebase* vs #14 (command decision vs team process) ✅ ·
*EXPLAIN* vs #2 (reading plans vs adding indexes) ✅ · *2FA rollout* vs #11 (account layer vs
passkey concept) ✅ · *embeddings* vs #1 (component deep-dive vs system overview) ✅.

## 6. Internal linking plan (Part G)

**Current verified state**
- Render-time auto-link map: **34 curated phrase→slug rules**, max 3/article, skips
  `<a>/<pre>/<code>`, no self-links, published slugs only → **7 articles** currently receive links.
- Related-posts rail: shared-tag score → same category → recency (verified working).
- Admin brief now has a **related-articles field** whose slugs are validated server-side against
  *published* posts only — planned links can never point at nonexistent pages.

**Rules for editors (contextual links)**
- Link pillar ↔ supporting **both directions** once an expansion is live;
- Prefer descriptive anchors over repeated exact-match keywords; max ~1 contextual link per
  150–200 words of prose; never inside code blocks;
- Cross-cluster links only where the reader genuinely benefits (#24↔#10, #16↔#4, #25↔#3 are approved);
- **Forbidden:** links to drafts, scheduled posts, or proposed-but-unwritten pages — enforced by
  slug validation at save, and by this plan's convention (Proposed pages get linked **only after publish**).

**Growth sequence**
1. As each P0–P2 expansion lands, add its 2–4 strongest recurring phrases to the auto-map (new destinations only);
2. New gap articles: backfill links from their cluster pillar in the same week they publish;
3. Re-run `tests/smoke-tests.ps1` after every map change (asserts no broken internal links).
