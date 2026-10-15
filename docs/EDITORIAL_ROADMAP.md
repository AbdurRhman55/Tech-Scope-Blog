# TechScope — Prioritization & 30-Day Editorial Roadmap (Part J)

**Date:** 2026-10-12 · **Status:** plan active · rewrites/summaries require human review before publishing.
**Companion docs:** `CONTENT_INVENTORY.md` (per-article audit) · `CLUSTER_ARCHITECTURE.md`
(clusters/pillars/links) · `EDITORIAL_PLAYBOOK.md` (briefs, checklist, originality, visuals) ·
`SEO_AUDIT.md` (technical) · `DEPLOYMENT.md` (launch + Search Console) ·
`drafts/serverless-expansion-draft.md` (sample reviewable draft).

> **Safety rules in force:** no published article was silently rewritten, merged, or deleted;
> content changes ship as reviewable drafts; no keyword volumes, rankings, or traffic figures are
> claimed anywhere in these docs (no keyword tool was available).

---

## 1. Prioritization framework (applied order)

1. **Critical factual/technical errors** — none confirmed today; ⚠-flagged claims (inventory §4)
   must be checked *before* they are repeated in expansions.
2. **Important articles that fail to answer their question** — #7 (31 words) first.
3. **Strong-topic articles missing practical depth** — thin pillars and thin pages (#1, #8, #2,
   #6, #21, #18, #3).
4. **Pillar pages that organize real supporting content** — C1/C2/C3/C4 pillars in §2 of the
   architecture doc.
5. **Internal-linking improvements** — pillar↔supporting links added as expansions land.
6. **New supporting articles with distinct intents** — only those passing the gap test
   (architecture doc §5), never to complete a diagram.

**Scoring used:** audience importance × current shortfall × cluster role × dependency order ×
editorial capacity. Search volume was **not** used (no data available — none invented).

---

## 2. 30-day plan

### Week 1 — Metadata + critical thin content
- [ ] Write unique meta titles (≤60 ch) + descriptions (140–160 ch) for **all 24** posts
      (admin → SEO panel; longest task — batch by cluster: C1→C9).
- [ ] Review `drafts/serverless-expansion-draft.md`; human approves → publish → **#7 fixed (P0)**.
- [ ] Expand **#21** (94w) and **#18** (98w) to article-grade depth (Playbook §3 structure);
      add TOC check (auto-appears at ≥300w / ≥3 H2s).
- [ ] Create editorial briefs (`brief_ready`) for the Week 2 expansion targets: #1, #8, #2, #6.

### Week 2 — AI cluster pillars (C1)
- [ ] Expand **#1** into the LLM pillar: tokens, embeddings, attention walkthrough, evaluation
      intro; add the flow diagram (Playbook §8); cite primary sources in References.
- [ ] Expand **#8** with a criteria/decision table (no invented cost figures).
- [ ] Expand **#9** with a concrete context-assembly example; **#24** with the dated VRAM/quantization table.
- [ ] Add pillar↔supporting auto-map phrases for new recurring terms; re-run smoke tests.
- [ ] Verify the #24↔#10 hardware cross-link renders contextually.

### Week 3 — Programming + Data pillars (C2/C3)
- [ ] ⚠ Review **#2** "100x" title claim with the owner (soften vs substantiate) → then expand
      into the SQL pillar with real `EXPLAIN` output (captured, not fabricated).
- [ ] Expand **#6** toward Maintainable PHP (before/after diffs, tested snippets).
- [ ] Improve **#14** (command examples) and **#15** (verify every code sample compiles).
- [ ] Publish gap article **SQL: reading EXPLAIN** (brief first) and **Testing PHP: a practical
      first suite** — link both from their pillars the same week.
- [ ] **#10** hardware guide: verify/annotate vendor claims, add review date (Update class).

### Week 4 — Remaining depth + measurement
- [ ] Expand **#3** (freelance anchor) with clearly-hypothetical examples; improve **#12**
      (worked calculation), **#13** (code sample), **#19** (example answers), **#23** (templates).
- [ ] Complete draft **#25** (context switching) → brief → review → publish or keep draft.
- [ ] If deployed: Search Console sitemap submission + Coverage/Performance review
      (follow `DEPLOYMENT.md`); tune titles/descriptions for impressions-vs-CTR mismatches —
      **no ranking/traffic claims until real GSC data exists**.
- [ ] Pick next month's 2–4 gap articles from the architecture map based on capacity + real GSC data.

### Ongoing rules
- Brief before drafting; human review before publishing; review status updated honestly.
- Never fabricate benchmarks, quotes, statistics, case studies, or screenshots.
- Version/date-stamp fast-moving claims (AI, hardware, browser support, pricing).
- Expand only where it adds usefulness — no forced word counts, no padding.
- Re-verify flagged claims (inventory §4) at each quarterly content review; hardware/security
  pages annually (year is in #10's title).
- Re-run `tests/smoke-tests.ps1` after template, auto-map, or admin changes.

---

## 3. Implementation status (updated this session)

| Change | Status |
|---|---|
| Editorial brief table + admin fields (audience, question, intent, outline, references, related, review status/notes) | ✅ Implemented & tested (90/90) |
| Review-status badges in admin posts list | ✅ Implemented & tested |
| Public *References & further reading* rendering (URL-validated) | ✅ Implemented & tested |
| Responsive content tables (scroll wrapper + styles), figcaption styling | ✅ Implemented & tested |
| Related-slugs validated to published posts only (no phantom links) | ✅ Implemented & tested |
| Reading time, heading IDs, TOC gating, auto-internal-links, sitemap/canonical/robots | ✅ Implemented & tested (prior session) |
| Cluster architecture, inventory, playbook, roadmap docs | ✅ Delivered |
| Per-article rewrites/expansions | 🔶 Editorial — drafts via briefs required |
| Meta titles/descriptions (24 posts) | 🔶 Editor task (form + preview ready) |
| Gap articles (architecture §4) | 🔶 Editorial — brief-first rule |
| ⚠-flagged claims (#2 title, #10/#24 hardware, #11 support matrix, #4 citations, #13 claim) | ⛔ **Human subject review required** |
| Ranking/traffic expectations, GSC baselines | ⛔ Not claimed — only measurable on the live host |
