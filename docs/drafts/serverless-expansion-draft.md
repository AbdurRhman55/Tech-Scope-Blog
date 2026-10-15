# TechScope — Reviewable Content Draft #1

> **Status: NOT PUBLISHED — awaiting human review.**
> Source article: `serverless-architecture-when-it-shines-and-when-it-fails` (31 words — critical thin content).
> The original text is preserved verbatim as the opening section. This draft expands it with
> analysis and general engineering knowledge only — **no fabricated benchmarks, case studies,
> statistics or personal experience**. Claims are deliberately hedged where they are trade-off
> judgements rather than verified measurements.

**Editorial brief**
- **Audience:** developers/architects evaluating serverless for production
- **Intent:** evaluation/decision — "should we use serverless for this service?"
- **Main question:** when does serverless help, and when does it cost you?
- **Cluster:** Cloud & Infrastructure (category: Technology) — links to: deployment article (planned), monitoring article (planned), local LLMs (GPU cost context), SQL indexing (stateful workloads)
- **Sources to add before publishing:** AWS/Lambda & Cloud Functions pricing/limits documentation (primary), 12-factor config principles

---

## Proposed article body (HTML — paste into the admin editor after review)

```html
<p>Serverless promises zero ops. But every architecture has a bill — and the bill is not always money.</p>

<h2>The direct answer</h2>
<p>Use serverless when your workload is <strong>spiky, event-driven and stateless</strong>, and the team values shipping over infrastructure ownership. Avoid it when you need sustained high CPU, long-running connections, predictable per-request cost at scale, or tight control over the runtime environment. Serverless is not "no infrastructure" — it is <em>someone else's infrastructure, with different knobs</em>.</p>

<h2>Where it shines</h2>
<ul>
<li><strong>Spiky, event-driven workloads.</strong> Traffic that peaks in bursts (webhooks, scheduled jobs, image processing) benefits from paying only while requests run.</li>
<li><strong>Backends with unpredictable traffic.</strong> You stop guessing capacity; the platform scales to zero and back up automatically.</li>
<li><strong>Glue between services.</strong> Event routing, cron-style tasks and API fronts are a few lines of code with no server patching.</li>
<li><strong>Small teams.</strong> Patching, OS updates and load-balancer tuning are handled by the provider.</li>
</ul>

<h2>Where it fails</h2>
<ul>
<li><strong>Long-lived connections &amp; streaming.</strong> WebSockets and long polls fight the request/response model; you end up paying for keep-alives or adding a separate realtime service.</li>
<li><strong>Sustained heavy compute.</strong> Per-request pricing can exceed an always-on instance once CPU runs continuously; measure before assuming it is cheaper.</li>
<li><strong>Cold starts.</strong> Intermittent traffic means first requests pay a latency penalty. Usually tolerable, sometimes unacceptable for latency-sensitive endpoints.</li>
<li><strong>State and data locality.</strong> Databases, caches and file systems live outside the function — network limits, connection pooling and VPC configuration become your problem again.</li>
<li><strong>Vendor-shaped complexity.</strong> Queues, triggers and IAM policies are provider-specific. Migrating later is a rewrite, not a config change.</li>
</ul>

<h2>A simple decision checklist</h2>
<ol>
<li>Is the workload event-driven and bursty rather than constant?</li>
<li>Can each request finish in seconds, without holding state?</li>
<li>Is the team small enough that "no servers to patch" outweighs vendor lock-in?</li>
<li>Have you estimated cost at <em>your</em> real traffic — not the pricing calculator's demo tier?</li>
<li>Is a cold start on the first request acceptable to your users?</li>
</ol>
<p>If most answers are "yes", serverless will likely simplify your life. If not, a container or small VM will be cheaper and easier to reason about.</p>

<h2>Common mistakes</h2>
<ul>
<li><strong>Wrapping a monolith in a function.</strong> Moving the same tightly-coupled code into a function changes the billing model, not the architecture.</li>
<li><strong>Ignoring cold-start profiles</strong> until after launch — test with production-like dependencies (VPC, SDKs, connection setup).</li>
<li><strong>Skipping observability.</strong> Distributed traces matter more when every handler is its own deployment unit.</li>
</ul>

<h2>Conclusion and next step</h2>
<p>Serverless is a strong default for bursty, stateless APIs and background jobs — and a poor one for sustained compute and stateful systems. Start with one candidate service, measure cost and latency under real traffic for two weeks, and let the numbers decide. If you keep it, make monitoring your next priority: serverless hides the machine, so your alerts are the only window into it.</p>
```

**Checklist for the reviewer**
- [ ] Technical claims reviewed (cold-start behaviour, pricing logic are stated qualitatively — no numbers invented)
- [ ] Add primary-source links (provider limits/pricing docs) before publishing
- [ ] Set **meta description**: *"Serverless looks like zero ops — until the trade-offs hit production. Here's when to use it, when to avoid it, and a 5-question decision checklist."*
- [ ] Set **meta title**: `Serverless Architecture: When It Shines and When It Fails`
- [ ] Save as draft first; publish only after review
