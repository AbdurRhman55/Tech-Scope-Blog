# TechScope — Verified Content Inventory (Part C)

**Date:** 2026-10-12 · **Source:** live database (`pro_blog`), queried directly — no invented records.
**Scope:** 25 posts (24 published, 1 draft), 9 categories, 50 tags.

> **Verification legend**
> ✅ **Verified** — read from the database or rendered output in this session (slug, title, category,
> tags, status, word count, H2 count, excerpt, metadata state).
> 📋 **Prior audit** — finding carried forward from `EDITORIAL_ROADMAP.md` §1 (produced from content
> inspection on 2026-10-12).
> ⚠ **Flag** — claim/figure that needs human verification before being repeated or expanded.
> 💡 **Inferred** — reader/intent/question derived from title + excerpt (not a ranking-data claim).

**Classification scheme (per brief):** Keep · Improve · Expand · Update · Consolidate ·
Redirect/retire · Research-further (flag). **Priority:** P0 (critical) → P4 (backlog).

---

## 1. Master table (✅ verified)

~Words ≈ chars/6 of stored content; H2s counted from markup; **no article has a meta description**.

| ID | Slug (URL = `/post/<slug>`) | Category → Cluster | Tags | ~Words | H2s | Status |
|----|-----------------------------|--------------------|------|-------:|-----:|--------|
| 1 | how-large-language-models-actually-work | AI → C1 | ai, llm, transformers | 209 | 4 | published |
| 8 | rag-vs-fine-tuning-llm | AI → C1 | ai, fine-tuning, rag | 141 | 3 | published |
| 9 | context-engineering-over-prompt-engineering | AI → C1 | ai, llm, prompting | 109 | 2 | published |
| 24 | local-llms-laptop-guide | AI → C1 | ai, hardware, local-llm | 107 | 3 | published |
| 6 | clean-code-php-patterns | Programming → C2 | best-practices, clean-code, php | 167 | 4 | published |
| 14 | git-workflows-team-scale | Programming → C2 | git, programming, team-work | 108 | 2 | published |
| 15 | typescript-tips-hard-way | Programming → C2 | javascript, programming, typescript | 114 | 3 | published |
| 2 | sql-indexing-practical-guide | Data Science → C3 | database, performance, sql | 154 | 3 | published |
| 5 | data-visualization-principles | Data Science → C3 | analytics, data-visualization, design | 134 | 3 | published |
| 12 | cohort-analysis-retention-guide | Data Science → C3 | analytics, data-science, retention | 112 | 3 | published |
| 13 | feature-engineering-tabular-data | Data Science → C3 | data-science, features, machine-learning | 102 | 3 | published |
| 7 | serverless-architecture-when-it-shines-and-when-it-fails | Cloud → C4 | architecture, cloud, serverless | **31** | 1 | published |
| 16 | how-to-read-research-paper | Research → C5 | academic, research, study | 102 | 3 | published |
| 17 | reproducible-research-environment | Research → C5 | data-science, reproducibility, research | 105 | 3 | published |
| 4 | evidence-based-learning-techniques | Student & Career → C6 | productivity, students, study | 143 | 3 | published |
| 18 | student-portfolio-gets-interviews | Student & Career → C6 | career, portfolio, students | 98 | 3 | published |
| 19 | interview-prep-star-method | Student & Career → C6 | career, interview, students | 107 | 3 | published |
| 3 | freelance-career-pricing-clients-burnout | Freelancing → C7 | career, freelancing, productivity | 149 | 4 | published |
| 22 | freelance-niche-positioning | Freelancing → C7 | career, freelancing, positioning | 103 | 3 | published |
| 23 | client-onboarding-emails-scope-creep | Freelancing → C7 | clients, communication, freelancing | 112 | 3 | published |
| 20 | deep-work-realistic-system | Productivity → C8 | focus, habits, productivity | 128 | 3 | published |
| 21 | zettelkasten-note-taking-simple | Productivity → C8 | notes, pkms, productivity | 94 | 3 | published |
| 25 | hidden-cost-context-switching | Productivity → C8 | *(none)* | 20 | 0 | **draft** |
| 10 | developer-hardware-guide-2026 | Technology → C9 | dev-setup, hardware, technology | 108 | 3 | published |
| 11 | passkeys-passwordless-web | Technology → C9 | security, technology, web | 106 | 2 | published |

**Cross-cutting verified findings**
- Average published depth ≈ **115 words**; the only draft (#25) is 20 words (0 H2s, no tags).
- Metadata: **0/24** meta titles, **0/24** meta descriptions (admin SEO panel ready).
- Prior audit 📋: every published article already has H2 structure and an excerpt;
  **zero** pre-existing authored cross-links (render-time auto-map now links 7 articles).
- No article was rewritten, consolidated, or deleted by any automated step.

---

## 2. Per-article audit cards

### Cluster C1 — Artificial Intelligence

**#1 [how-large-language-models-actually-work](/post/how-large-language-models-actually-work)** — *How Large Language Models Actually Work*
- 💡 Audience: developers/tech learners new to LLMs · Intent: **Informational (how-it-works)**
- Main question (excerpt ✅): what happens inside an LLM from prompt to prediction (tokens, attention, parameters)?
- Related: #8, #9 (shared llm/ai tags), #24 (cluster), #10 (hardware, cross-cluster)
- Pillar: **candidate → C1 pillar.** Currently 209w/4 H2 — 📋 the thinnest pillar candidate.
- Depth gaps 📋: needs tokenization worked example, attention walkthrough, evaluation intro, a flow diagram (Playbook §8).
- ⚠ Any benchmark/parameter figures added during expansion must cite primary sources with dates.
- Action: **Expand → P1** · Meta: write unique title+description.

**#8 [rag-vs-fine-tuning-llm](/post/rag-vs-fine-tuning-llm)** — *RAG vs Fine-Tuning: Choosing the Right Way to Specialise an LLM*
- 💡 Audience: ML engineers deciding an implementation path · Intent: **Comparison/decision**
- Main question: when to retrieve vs when to retrain (cost, latency, maintenance)?
- Related: #1, #9, #24 (cluster); future: RAG-failures + quantization articles (distinct intents).
- Pillar: **sub-pillar (comparison page)** within C1 — 📋 expand with a criteria/decision table.
- ⚠ Cost/latency statements must be sourced or framed as trade-off reasoning, not benchmarks.
- Action: **Expand → P1** · Meta: needed.

**#9 [context-engineering-over-prompt-engineering](/post/context-engineering-over-prompt-engineering)** — *Prompt Engineering Is Dead. Long Live Context Engineering.*
- 💡 Audience: AI practitioners · Intent: **Informational/opinion**
- Main question: why does designing the model's surrounding context beat clever phrasing?
- Related: #1 (LLM basics), #8 (choice framework) — cross-link only (prior overlap check ✅ distinct).
- Pillar: supporting → #1 · Depth 📋: 109w, 2 H2 — needs concrete context-assembly example.
- Action: **Improve → P2** · Meta: needed. Headline is a hook — keep claims defensible in body.

**#24 [local-llms-laptop-guide](/post/local-llms-laptop-guide)** — *Local LLMs on a Laptop: What Runs, and What It Is Actually Good For*
- 💡 Audience: privacy-conscious developers · Intent: **Tutorial/buying-guide (evaluation)**
- Main question: what can I actually run offline in 2026, and is it useful?
- Related: #1 (concepts), #10 (hardware — **strong cross-cluster link**), #8 (model choice).
- Pillar: supporting → #1; itself a candidate "Running Local LLMs" pillar 📋 per brief.
- ⚠ **Version-sensitive:** VRAM/RAM/quantization figures and model sizes change fast —
  date-stamp every hardware claim, cite model cards; flag for **Research-further** review.
- Action: **Expand + Update → P2** (add hardware/VRAM table; yearly review cadence).

### Cluster C2 — Programming & Software Engineering

**#6 [clean-code-php-patterns](/post/clean-code-php-patterns)** — *Clean Code in PHP: Patterns Every Developer Should Know*
- 💡 Audience: PHP developers (junior→mid) · Intent: **Informational/educational**
- Main question: which practical habits keep a PHP codebase maintainable?
- Related: #15 (code quality), #14 (team workflows), #2 (prepared statements angle — cross-cluster).
- Pillar: **candidate → C2 pillar** ("Maintainable PHP" per brief); 📋 expand with before/after diffs.
- Action: **Expand → P2** (pillar depends on it; editors may broaden title if scope grows — URL stays).

**#14 [git-workflows-team-scale](/post/git-workflows-team-scale)** — *Git Workflows That Scale Past Five Developers*
- 💡 Audience: teams adopting a branching model · Intent: **Evaluation/decision**
- Main question: which branching setup stops merge pain as the team grows?
- Related: #6, #15; gap article "Git merge vs rebase" is **distinct** (command-level decision vs team process).
- Depth 📋: 108w, 2 H2 — add command examples. Action: **Improve → P3** · Meta: needed.

**#15 [typescript-tips-hard-way](/post/typescript-tips-hard-way)** — *TypeScript Tips You Learn the Hard Way*
- 💡 Audience: newcomers to TypeScript · Intent: **Educational (tips/list)**
- Main question: which strict-mode/union/array pitfalls bite newcomers and how do I avoid them?
- Related: #6 (code quality), #14. Depth 📋: add runnable code samples per tip.
- Action: **Improve → P3** · Verify each snippet compiles before publishing (Playbook §6).

### Cluster C3 — Data Science & Analytics

**#2 [sql-indexing-practical-guide](/post/sql-indexing-practical-guide)** — *SQL Indexing: A Practical Guide to 100x Faster Queries*
- 💡 Audience: backend devs/analysts with slow queries · Intent: **Tutorial**
- Main question: how do indexes actually speed queries, and which index do I add?
- Related: #6 (prepared statements), #13/#12/#5 (cluster); gap "reading EXPLAIN" supports it.
- Pillar: **candidate → C3 pillar** ("Practical SQL Performance Optimization").
- ⚠ **"100x" title claim cannot be substantiated from content alone → flag: human review**
  (soften to a defensible promise, e.g. "orders of magnitude faster" — editor decision; URL unchanged).
- Depth 📋: needs real `EXPLAIN` output example. Action: **Expand + Research-further → P1**.

**#5 [data-visualization-principles](/post/data-visualization-principles)** — *From Spreadsheets to Storytelling: Data Visualization Principles*
- 💡 Audience: analysts reporting findings · Intent: **Informational/educational**
- Main question: which design principles make insights obvious instead of overwhelming?
- Related: #12 (analytics), #13; charts based on real data preferred over decoration (Playbook §8).
- Depth 📋: add example charts (correct/incorrect pairs). Action: **Improve → P3**.

**#12 [cohort-analysis-retention-guide](/post/cohort-analysis-retention-guide)** — *Cohort Analysis for Beginners: Reading Retention Like a Pro*
- 💡 Audience: junior analysts/product people · Intent: **Tutorial/how-to**
- Main question: how do I build and read a cohort table without being misled by blended metrics?
- Related: #5, #13 (cluster); #17 (reproducibility, cross-cluster). Depth 📋: **worked calculation missing.**
- Action: **Improve → P3** (add one fully-worked retention example with made-up-but-labeled data).

**#13 [feature-engineering-tabular-data](/post/feature-engineering-tabular-data)** — *Feature Engineering Still Beats Bigger Models on Tabular Data*
- 💡 Audience: data scientists/ML engineers · Intent: **Informational/debate**
- Main question: why do domain features still win on structured tables?
- Related: #12, #5; #1/#8 (AI cluster cross-link — "bigger models" framing). Depth 📋: add code sample.
- ⚠ "still beats" is a claim — support with citations or soften during review.
- Action: **Improve + Research-further → P3**.

### Cluster C4 — Cloud Computing & Infrastructure

**#7 [serverless-architecture-when-it-shines-and-when-it-fails](/post/serverless-architecture-when-it-shines-and-when-it-fails)** — *Serverless Architecture: When It Shines and When It Fails*
- 💡 Audience: architects/devs evaluating serverless · Intent: **Evaluation (pros/cons decision)**
- Main question: where does serverless help, and where does it hurt in production?
- Related: none in-cluster (**only C4 article**); cross: #2 (perf), future deployment/monitoring articles.
- Pillar: **→ C4 pillar** (decision guide per brief). ⚠ **31 words / 1 H2 — critical thin content.**
- Reviewable expansion draft exists: `docs/drafts/serverless-expansion-draft.md` (NOT published).
- Action: **Expand → P0** (review & publish draft) · Meta: needed.

### Cluster C5 — Research & Technical Evaluation

**#16 [how-to-read-research-paper](/post/how-to-read-research-paper)** — *How to Read a Research Paper Without Falling Asleep*
- 💡 Audience: students/developers reading papers · Intent: **Method/how-to**
- Main question: how do I get to comprehension of a paper efficiently (three-pass method)?
- Related: #17 (cluster), #4 (study methods, cross-cluster), #21 (notes, cross-cluster).
- Pillar: none required — self-contained method page. Depth 📋: worked example on one real paper helps.
- Action: **Improve → P4** · Meta: needed.

**#17 [reproducible-research-environment](/post/reproducible-research-environment)** — *Reproducible Research: Notebooks, Environment Pins and Data Versioning*
- 💡 Audience: analysts/researchers · Intent: **Tutorial/infrastructure**
- Main question: what infrastructure stops "nobody can rerun my analysis six months later"?
- Related: #16 (cluster), #13/#12 (data-science tag), #21 (notes workflow).
- Depth 📋: add config examples (pinning commands). ⚠ Tool commands must be version-checked (Playbook §6).
- Action: **Improve → P3** · Meta: needed.

### Cluster C6 — Student & Career Development

**#4 [evidence-based-learning-techniques](/post/evidence-based-learning-techniques)** — *Study Smarter: Evidence-Based Learning Techniques for Students*
- 💡 Audience: students · Intent: **Informational/evidence review**
- Main question: which study methods have the strongest research support?
- Related: #18, #19 (cluster); #16 (research reading), #20 (habits, cross-cluster).
- ⚠ "strongest research" must cite the primary studies (dates) — verify citations before expanding.
- Depth 📋: expand + sources. Action: **Expand + Research-further → P3**.

**#18 [student-portfolio-gets-interviews](/post/student-portfolio-gets-interviews)** — *From Campus to Career: Building a Portfolio That Gets Interviews*
- 💡 Audience: students/junior devs · Intent: **How-to/career**
- Main question: how do I build a portfolio that answers "can you ship?" in seconds?
- Related: #19 (interviews), #4 (study); future: learning-roadmap article (distinct intent).
- Depth: **98w — thin** 📋. Action: **Expand → P2** · Meta: needed.

**#19 [interview-prep-star-method](/post/interview-prep-star-method)** — *Interview Prep That Works: The STAR Method and Beyond*
- 💡 Audience: students/job seekers · Intent: **How-to (with examples)**
- Main question: how do I structure behavioural/technical answers repeatably?
- Related: #18 (cluster), #4. Depth 📋: example answers missing. Action: **Improve → P3**.

### Cluster C7 — Technology Freelancing

**#3 [freelance-career-pricing-clients-burnout](/post/freelance-career-pricing-clients-burnout)** — *Building a Freelance Career That Lasts: Pricing, Clients and Burnout*
- 💡 Audience: freelancers aiming for sustainability · Intent: **Career guidance/overview**
- Main question: how do pricing, client selection and rest fit into a career that lasts?
- Related: #22 (positioning), #23 (onboarding) — intents verified distinct (prior check ✅); #20 (burnout angle).
- Pillar: natural **C7 anchor** (topic overview) without forcing sub-page structure.
- Depth 📋: expand + case-style examples (must be clearly hypothetical — no invented case studies, Playbook §6).
- Action: **Expand → P2** · Meta: needed.

**#22 [freelance-niche-positioning](/post/freelance-niche-positioning)** — *Positioning Your Freelance Niche: Charge More by Choosing Less*
- 💡 Audience: generalist freelancers · Intent: **Career/strategy guidance**
- Main question: why does narrowing my niche let me charge more?
- Related: #3, #23 (cluster). Depth 📋: expand. Action: **Improve → P4** · Meta: needed.

**#23 [client-onboarding-emails-scope-creep](/post/client-onboarding-emails-scope-creep)** — *Client Onboarding Emails That Prevent Scope Creep*
- 💡 Audience: freelancers/agency devs · Intent: **How-to/templates**
- Main question: what email sequence sets boundaries in week one without friction?
- Related: #22, #3 (cluster). Depth 📋: add email templates (usable, not fabricated results).
- Action: **Improve → P3** · Meta: needed.

### Cluster C8 — Productivity & Developer Workflows

**#20 [deep-work-realistic-system](/post/deep-work-realistic-system)** — *Deep Work in a Distracted World: A Realistic System*
- 💡 Audience: knowledge workers/developers · Intent: **How-to (system)**
- Main question: how do I build an environment where focus is the default instead of relying on willpower?
- Related: #21 (notes), #4 (habits), #3 (burnout, cross-cluster); draft #25 (direct companion).
- Depth 📋: expand. Action: **Improve → P4** · Meta: needed.

**#21 [zettelkasten-note-taking-simple](/post/zettelkasten-note-taking-simple)** — *Note-Taking for Knowledge Workers: Zettelkasten Made Simple*
- 💡 Audience: developers/researchers with a notes habit · Intent: **How-to (method)**
- Main question: how do I turn a folder of highlights into linked, reusable notes?
- Related: #20 (cluster), #16/#17 (research workflow cross-links).
- Depth: **94w — thin** 📋. Action: **Expand → P2** · Meta: needed.

**#25 [hidden-cost-context-switching](/post/hidden-cost-context-switching)** *(DRAFT)* — *The Hidden Cost of Context Switching*
- 💡 Audience: knowledge workers/teams · Intent: **Informational (problem → cost)**
- Main question: what does task switching actually cost, and what reduces it?
- Related: #20 (companion), #3 (burnout cross-link). State: 20w outline, no tags, no excerpt depth.
- Rule: remains **draft** until human-reviewed (never auto-publish).
- Action: **Complete draft → P3** · Add tags (productivity, focus) before publishing.

### Cluster C9 — Technology, Hardware & Digital Security

**#10 [developer-hardware-guide-2026](/post/developer-hardware-guide-2026)** — *The 2026 Developer Hardware Guide: What Actually Matters*
- 💡 Audience: developers buying/upgrading machines · Intent: **Evaluation/buying guide**
- Main question: which hardware specs matter for real dev workloads (cores, RAM, NPU, battery)?
- Related: #24 (**strong**: VRAM/local-LLM hardware), #11 (same category).
- ⚠ **Dated by design (year in title):** needs annual re-verification of CPU/NPU/battery claims —
  **Research-further** flag; do not restate vendor claims without sources.
- Action: **Update → P2** (review claims, add purchase-date context) · Meta: needed.

**#11 [passkeys-passwordless-web](/post/passkeys-passwordless-web)** — *Passkeys, End-to-End Encryption and the Passwordless Web*
- 💡 Audience: web developers shipping auth · Intent: **Informational + rollout guidance**
- Main question: why do passkeys succeed where SMS codes failed, and how do I roll them out?
- Related: #10 (cluster); future: 2FA/migration article (distinct intent). #6 (secure coding angle, weak).
- ⚠ Browser/OS support matrix changes — date-stamp it; cite FIDO/W3C sources.
- Depth 📋: add browser-support table + rollout steps. Action: **Improve + Research-further → P3**.

---

## 3. Consolidation / redirect analysis (verified)

| Check | Result |
|---|---|
| Title-similar pairs → intent compared | freelance trio, AI trio, productivity trio — all **distinct intents** (prior check ✅, re-confirmed from excerpts) |
| Substantially overlapping content? | **No** — no two articles answer the same question |
| Consolidate candidates | **None** |
| Redirect/retire candidates | **None** — no page is redundant |
| Taxonomy conflicts | Cloud Computing (owner-created, id 9) is populated (1 post) → keep; revisit at ≥5 posts vs folding into Technology |

**Action for every published article: none deleted, none merged, none redirected.**

## 4. Priority queue (summary)

| Priority | Articles | Why |
|---|---|---|
| **P0** | #7 | Critical thin content (31w), draft ready |
| **P1** | #1, #8, #2 · *meta for all 24* | Pillar candidates + substantiation flag; metadata blocks snippets |
| **P2** | #6, #21, #18, #9, #24, #10, #3 | Pillar support + thin articles + dated-topic update |
| **P3** | #14, #15, #12, #13, #5, #17, #19, #4, #11, #23, #25 | Depth/examples/flags |
| **P4** | #16, #22, #20 | Useful already; improve when capacity allows |
