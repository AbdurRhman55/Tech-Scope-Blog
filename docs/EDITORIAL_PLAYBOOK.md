# TechScope — Editorial Playbook (Parts E, F, H-editorial, I)

**Date:** 2026-10-12 · **Purpose:** the content-quality system for existing and future articles.
**Where it lives in the tooling:** every field in §1 is implemented in the admin editor
(*Editorial brief & review* section of `admin/post-edit.php`, stored in the `editorial_briefs`
table); §5's quick subset is the in-editor checklist; §9 defines the review workflow.

> Depth ≠ word count. An article is complete when it **satisfies its search intent, explains what
> the reader needs, supports its claims, and lets the reader act** — not when it hits a number.

---

## 1. Editorial brief template (E1)

Fill **before drafting or substantially revising** (admin → Editorial brief & review):

| Brief field (admin) | The question it answers |
|---|---|
| Target audience | Who is the intended reader? (role + level, e.g. "junior backend devs with slow queries") |
| Primary question | What problem brought them here, and what is **the one question** the article must answer? |
| *(in the brief's notes)* Direct answer | The direct answer, stated in 2–3 sentences (this becomes the opening) |
| Search intent | Which E2 type? (informational / tutorial / troubleshooting / comparison / evaluation / reference / career) |
| Supporting questions | Follow-up questions the article should cover (one per line) |
| Proposed outline | H2/H3 sequence (one heading per line) — the structure from §3 adapted to intent |
| References | Sources to consult (label + URL) — becomes the public *References* section |
| Related articles | Comma-separated **published** slugs genuinely relevant (auto-validated) |
| Review status / notes | Workflow state + editor feedback (§9) |

Background knowledge, required examples, limitations/failure cases and follow-up questions belong
in *Supporting questions* and *Review notes* — capture them, do not skip them.

## 2. Search-intent matching (E2)

| Intent | Article must contain | Shape |
|---|---|---|
| Informational | Direct answer early, definitions, mechanism, example | explain → show |
| Tutorial | Prerequisites, environment/versions, ordered steps, expected outcome, **verification step**, common errors, security notes | setup → steps → verify → troubleshoot |
| Troubleshooting | Symptoms first, diagnostic steps, causes, **verified fixes** | symptom → diagnose → fix → prevent |
| Comparison | Defined criteria, trade-off table, per-criterion analysis, fit statements ("choose X if…") | criteria → compare → decide |
| Evaluation | Who each option serves, constraints, honest limitations | needs → options → verdict-by-case |
| Reference | Precise facts, syntax, versions, edge cases — no padding | spec → examples → caveats |
| Career/workflow | Concrete artifacts (templates, checklists), realistic scenarios, boundaries of the advice | context → artifacts → application |

Do not force one structure on all articles. A debugging guide with no symptoms section, or a
comparison without criteria, is incomplete regardless of length.

## 3. Recommended article structure (E3 — flexible framework)

1. Specific H1 (existing title unless the editor changes it).
2. Short opening: the problem and the outcome the reader gets.
3. **Direct answer near the top** when the query style deserves it.
4. Brief overview of main points (only when useful).
5. Definitions / necessary background.
6. Step-by-step walkthrough or conceptual explanation.
7. Practical examples, code, calculations, or realistic scenarios.
8. Edge cases and common mistakes.
9. Trade-offs, limitations, alternatives.
10. Security / cost / performance / compatibility notes **where relevant**.
11. Relevant follow-up questions (link to related TechScope pages once live).
12. Concise conclusion or recommended next step.
13. References (admin brief → rendered automatically).
14. Contextual internal links to related TechScope articles.

Drop any section that does not help this article's reader.

## 4. Depth requirements (E4)

**Every major concept:** what it is → why it matters → how it works → example → when (not) to use
→ limitations/misconceptions → connection to the reader's problem.

**Tutorials:** prerequisites · environment & versions · ordered steps · expected outcomes ·
verification step · common errors · security implications · required vs optional steps clearly
marked.

**Comparisons:** criteria defined first · meaningful alternatives · trade-offs explicit · use
cases named · **no universal claims** · current cost/performance figures only from reliable,
cited sources with dates.

**Technical explanations:** unfamiliar terms defined · examples technically accurate · code
samples internally consistent · assumptions stated · why it works explained · alternatives
mentioned · **never invent test results**.

## 5. Completeness checklist (E5 — reviewer use)

*Supports judgment; no arbitrary word counts.*

- [ ] Opening answers the main question or clearly establishes the problem
- [ ] Intended audience is apparent from the reading level and examples
- [ ] Essential terms explained before use
- [ ] No important steps missing (for procedures: prerequisites → verification present)
- [ ] Claims supported by evidence or reliable references
- [ ] Examples realistic and correct; code samples tested and formatted
- [ ] Limitations and failure cases covered
- [ ] Most important follow-up questions answered or linked
- [ ] Headings meaningful and logically ordered (H2/H3, no keyword stuffing)
- [ ] Paragraphs add information — no repetition or padding
- [ ] Internal links genuinely relevant (and only to published pages)
- [ ] Images/diagrams useful and accurate; alt text describes the content
- [ ] Conclusion matches what the article actually argued
- [ ] Version-sensitive details carry version/date stamps
- [ ] Materially more useful than a shallow summary of the topic
- [ ] Originality & sourcing rules (§6) respected
- [ ] Review status set honestly in the brief (never "Approved" without a human reviewer)

*The 9-point quick version lives inside the editor as the "Editorial checklist" panel.*

## 6. Originality, accuracy & research rules (F)

- Never copy or lightly paraphrase competing articles; write from primary sources.
- **Never invent** sources, studies, quotations, benchmarks, statistics, or firsthand experience.
- Never claim a code example was executed unless it was tested; keep samples internally consistent.
- Separate documented facts from interpretation ("the docs state…" vs "in our reading…").
- Prefer official documentation for APIs, commands, and configuration; cite it in the brief's
  References (they render publicly).
- Verify versions/dates for fast-moving topics (AI models, browser support, cloud pricing); add
  "as of \<date\>" stamps.
- State uncertainty when evidence is incomplete; **flag unverifiable claims for human review**
  (current flags: #2 "100x" title, #10 hardware vendor claims, #24 hardware figures,
  #11 browser-support matrix, #4 research citations, #13 "still beats").
- Do not fabricate search-engine guidance or promise rankings/timeframes.
- No keyword stuffing or irrelevant keyword insertion.
- Do not invent author credentials, editorial experience, or case studies — hypothetical
  examples must be labeled as such.

## 7. Internal-linking rules for editors (G — editorial side)

- Record genuinely relevant related articles in the brief (auto-validated against published slugs);
- When expanding a pillar, ensure links flow **both ways** (add auto-map phrases for recurring terms);
- Descriptive anchors; avoid repeating the same exact-match anchor across articles;
- Link only where the reader benefits — never one automatic link per keyword occurrence;
- Proposed pages are linked **only after they are published** (mechanically enforced at save).

## 8. Visual content requirements (Part I)

**Formats that earn their place:** architecture/workflow diagrams, genuine software screenshots
(with the tool/version noted), annotated examples, charts from **actual data**, comparison tables,
step-by-step illustrations, code samples styled as blocks.

**For each visual, record:** purpose · section it belongs to · what it demonstrates · whether it
must come from a real application · diagram vs screenshot choice · accurate alt text.

| Cluster | Visual opportunity | Section | Capture vs draw | Alt text direction |
|---|---|---|---|---|
| C1 | Transformer/LLM flow (prompt→tokens→attention→output) | pillar #1 | draw (SVG/diagram) | "Diagram: text enters tokenization, passes attention layers, produces next-token probabilities" |
| C1 | RAG vs fine-tuning decision tree | #8 | draw | "Flowchart: does the knowledge change often? → retrieval vs retraining paths" |
| C1 | VRAM/quantization table | #24 | table (data from cited sources) | n/a — table header row must be present |
| C2 | Before/after code diffs | #6, #15 | real editor screenshot or styled diff block | "Diff: unprepared query replaced by prepared statement" |
| C3 | `EXPLAIN` output annotated | #2 | **real terminal capture** | "MySQL EXPLAIN plan with access type and key columns highlighted" |
| C3 | Correct vs misleading chart pair | #5 | draw/plot from real or clearly-labeled sample data | "Left: same data plotted misleadingly truncated axis; right: honest zero baseline" |
| C4 | Serverless request lifecycle | #7 | draw | "Diagram: request → gateway → function → managed service → response" |
| C5 | Three-pass paper reading loop | #16 | draw | "Circular diagram: pass 1 overview, pass 2 claims, pass 3 reproduce" |
| C6 | Portfolio structure example | #18 | real screenshot of a public portfolio **you own**, or wireframe | "Wireframe: project card showing problem, role, stack, outcome link" |
| C7 | Onboarding email sequence timeline | #23 | table/timeline | n/a |
| C9 | Browser-support matrix for passkeys | #11 | table from cited sources (dated) | n/a |

**Hard rules:** no fabricated screenshots of interfaces; no generic decorative images to inflate
image counts; respect licensing/attribution (existing repo images are CC0 topic photos with SVG
fallbacks); every image gets accurate alt text (`cover_alt` in admin for the featured image).
Capture anything that would be *presented as real* only from the real application.

## 9. Review workflow (implemented)

| Status (admin brief) | Meaning | Gate |
|---|---|---|
| `brief_ready` | Brief complete; not yet drafted | Intent + outline + references set |
| `draft_ready` | Draft written; waiting for review | Self-check §5 done by writer |
| `in_review` | Editor reviewing | Human reads against §5 + §6 |
| `approved` | Human-approved for publish | **Only a human sets this** |
| `needs_revision` | Returned with `review_notes` | Writer addresses notes, re-sets `draft_ready` |
| `none` | No brief yet | Default |

- Posts list shows the review status as a badge (admin → Posts).
- **Never publish unreviewed AI-assisted text**; drafts (`status=draft`) return 404 publicly.
- Related-article slugs in a brief are validated to published posts only (no future/phantom links).
- References set in the brief render automatically as *References & further reading* on the
  article page (URLs validated; non-http(s) lines are ignored).
