<?php
/**
 * ---------------------------------------------------------------------
 *  Seed script — inserts demo posts + tags (idempotent)
 *  Run once:  php seed.php
 * ---------------------------------------------------------------------
 */

// CLI-only: this script writes to the database. Never let an HTTP request
// execute it (a web hit on a shared/public URL would seed production data).
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('CLI only.');
}

require_once __DIR__ . '/includes/functions.php';

echo "Seeding demo content...\n";

$posts = [
    [
        'title'     => 'How Large Language Models Actually Work',
        'slug'      => 'how-large-language-models-actually-work',
        'category'  => 'artificial-intelligence',
        'excerpt'   => 'Tokens, attention, parameters — demystified. A practical walkthrough of what happens inside an LLM from prompt to prediction.',
        'content'   => '<p>Large language models look like magic until you break them down. At its core, an LLM is a very large function that predicts the next token given everything before it.</p>'
            . '<h2>1. Tokenization</h2><p>Your text is split into tokens — word fragments, punctuation, whitespace markers. Each token maps to an integer ID from the model vocabulary.</p>'
            . '<h2>2. Embeddings</h2><p>Every token becomes a vector of numbers. These vectors encode meaning: similar concepts cluster together in vector space.</p>'
            . '<h2>3. Attention</h2><p>The transformer&rsquo;s attention mechanism lets every token look at every other token and decide what matters. This is why context works.</p>'
            . '<blockquote>Attention is all you need — and it is exactly what makes context windows possible.</blockquote>'
            . '<h2>4. Prediction</h2><p>The model outputs a probability distribution over its vocabulary, samples one token, appends it, and repeats. That is autoregression.</p>'
            . '<h3>Key takeaways</h3><ul><li>LLMs predict tokens, not truths.</li><li>Quality of output depends heavily on context and prompting.</li><li>Temperature controls randomness.</li></ul>'
            . '<p>Understanding this pipeline makes prompt engineering far less mysterious: you are steering a probability machine, not talking to a person.</p>',
        'featured'  => 1,
        'views'     => 1284,
        'tags'      => ['llm', 'transformers', 'ai'],
    ],
    [
        'title'     => 'SQL Indexing: A Practical Guide to 100x Faster Queries',
        'slug'      => 'sql-indexing-practical-guide',
        'category'  => 'data-science',
        'excerpt'   => 'A slow query is usually a missing index. Learn B-trees, composite indexes and the exact rules DBAs use in production.',
        'content'   => '<p>Most &ldquo;slow database&rdquo; horror stories end the same way: a missing index. Here is how to think about indexing correctly.</p>'
            . '<h2>B-tree basics</h2><p>An index is a sorted structure that lets the engine find rows without a full table scan. Lookups go from O(n) to O(log n).</p>'
            . '<h2>Composite index order</h2><p>Column order matters. An index on (a, b) serves queries on <code>a</code> and <code>a + b</code>, but not on <code>b</code> alone.</p>'
            . '<pre><code>CREATE INDEX idx_posts_status_date ON posts(status, published_at);</code></pre>'
            . '<h2>When NOT to index</h2><ul><li>Tiny tables — the planner scans anyway.</li><li>Columns written constantly — index maintenance costs writes.</li><li>Low-cardinality columns alone (like a boolean).</li></ul>'
            . '<blockquote>Index the WHERE clause first, then JOIN and ORDER BY columns.</blockquote>'
            . '<p>Always verify with <code>EXPLAIN</code> before and after. Numbers beat intuition.</p>',
        'featured'  => 0,
        'views'     => 842,
        'tags'      => ['sql', 'database', 'performance'],
    ],
    [
        'title'     => 'Building a Freelance Career That Lasts: Pricing, Clients and Burnout',
        'slug'      => 'freelance-career-pricing-clients-burnout',
        'category'  => 'freelancing',
        'excerpt'   => 'Rate cards, scope creep, retainers and rest — hard-won lessons for turning freelancing into a sustainable career.',
        'content'   => '<p>Freelancing is easy to start and hard to sustain. The difference usually comes down to three things: pricing, boundaries and recovery.</p>'
            . '<h2>Price the value, not the hours</h2><p>Hourly billing punishes you for being fast. Project or value-based pricing lets your experience work for you.</p>'
            . '<h2>Kill scope creep early</h2><p>Every engagement needs a written scope. Anything outside it is a change request with a new quote — say this kindly, every single time.</p>'
            . '<h2>Retainers beat re-selling</h2><p>A modest monthly retainer gives you predictable income and saves the constant hunt for the next project.</p>'
            . '<h2>Rest is part of the job</h2><p>Burnout ends more freelance careers than competition does. Schedule downtime like a client meeting — non-negotiable.</p>'
            . '<p>Sustainable freelancing is a marathon of professionalism: deliver well, communicate clearly, and protect your energy.</p>',
        'featured'  => 0,
        'views'     => 567,
        'tags'      => ['freelancing', 'career', 'productivity'],
    ],
    [
        'title'     => 'Study Smarter: Evidence-Based Learning Techniques for Students',
        'slug'      => 'evidence-based-learning-techniques',
        'category'  => 'student-career',
        'excerpt'   => 'Spaced repetition, active recall and interleaving — the three methods with the strongest research behind them.',
        'content'   => '<p>Most students study in ways that feel productive but barely work. Highlighting and re-reading create an illusion of competence. The research points elsewhere.</p>'
            . '<h2>Active recall</h2><p>Closing the book and forcing yourself to retrieve information strengthens memory far more than passively reviewing it.</p>'
            . '<h2>Spaced repetition</h2><p>Reviewing material at growing intervals fights the forgetting curve. Tools like flashcard apps automate the schedule for you.</p>'
            . '<h2>Interleaving</h2><p>Mixing different problem types in one session feels harder — and produces better transfer than blocking one type at a time.</p>'
            . '<blockquote>Difficulty is not a sign of failure; it is part of the learning process.</blockquote>'
            . '<p>Combine all three: quiz yourself, spread sessions over days, and mix topics. Thirty minutes of this beats three hours of re-reading.</p>',
        'featured'  => 0,
        'views'     => 431,
        'tags'      => ['study', 'students', 'productivity'],
    ],
    [
        'title'     => 'From Spreadsheets to Storytelling: Data Visualization Principles',
        'slug'      => 'data-visualization-principles',
        'category'  => 'data-science',
        'excerpt'   => 'A chart is an argument. Learn the design principles that make insights obvious instead of overwhelming.',
        'content'   => '<p>Data visualization is not decoration — it is compression. A good chart lets someone see in two seconds what a table hides in twenty minutes.</p>'
            . '<h2>Start with the question</h2><p>Before choosing a chart, write the single sentence you want the reader to take away. Every element must serve it.</p>'
            . '<h2>Choose the form honestly</h2><ul><li>Comparison → bars</li><li>Change over time → lines</li><li>Correlation → scatter</li><li>Composition → stacked bars or treemaps</li></ul>'
            . '<h2>Remove everything else</h2><p>Gridlines, borders, redundant labels and 3D effects add ink without information. Chartjunk is real.</p>'
            . '<blockquote>If you have to explain the chart for a minute, the chart has failed.</blockquote>'
            . '<p>Label directly, use color with intent, and let the data — not the palette — be the star.</p>',
        'featured'  => 0,
        'views'     => 389,
        'tags'      => ['data-visualization', 'analytics', 'design'],
    ],
    [
        'title'     => 'Clean Code in PHP: Patterns Every Developer Should Know',
        'slug'      => 'clean-code-php-patterns',
        'category'  => 'programming',
        'excerpt'   => 'Naming, single responsibility, prepared statements and testing — practical PHP habits that keep codebases maintainable.',
        'content'   => '<p>Clean code is not about cleverness; it is about the next developer — who is usually you, six months from now, with no memory of this.</p>'
            . '<h2>Name things after intent</h2><p><code>$data</code> tells you nothing. <code>$publishedPosts</code> tells you everything. Names are documentation that cannot go stale.</p>'
            . '<h2>Small functions, single jobs</h2><p>If a function needs a comment to explain its steps, split it. Each function should do one thing and do it well.</p>'
            . '<h2>Never trust input</h2><p>Use PDO prepared statements for queries, escape output with <code>htmlspecialchars()</code>, and validate on the server — always.</p>'
            . '<pre><code>$stmt = $pdo->prepare("SELECT * FROM posts WHERE slug = ?");$stmt->execute([$slug]);</code></pre>'
            . '<h2>Test the behaviour, not the implementation</h2><p>Tests that break when you refactor without changing behaviour are slowing you down, not protecting you.</p>'
            . '<p>Maintainable code is a series of small, boring, disciplined decisions. That is the whole secret.</p>',
        'featured'  => 0,
        'views'     => 725,
        'tags'      => ['php', 'clean-code', 'best-practices'],
    ],
    // ---------------------------------------------------------------
    //  Batch 2 — fuller demo content
    // ---------------------------------------------------------------
    [
        'title'     => 'RAG vs Fine-Tuning: Choosing the Right Way to Specialise an LLM',
        'slug'      => 'rag-vs-fine-tuning-llm',
        'category'  => 'artificial-intelligence',
        'excerpt'   => 'When to retrieve, when to retrain — a decision framework with cost, latency and maintenance trade-offs.',
        'content'   => '<p>Teams reach for fine-tuning when they should be retrieving, and vice versa. The choice is less about hype and more about where your knowledge lives.</p>'
            . '<h2>Retrieval-augmented generation</h2><p>RAG keeps knowledge outside the model in a searchable index. You can update facts instantly, cite sources and keep the base model frozen.</p>'
            . '<h2>Fine-tuning</h2><p>Fine-tuning changes behaviour: tone, format, domain vocabulary. It is excellent for style and task patterns, poor at remembering fresh facts.</p>'
            . '<blockquote>If the knowledge changes weekly, retrieve. If the behaviour never changes, fine-tune.</blockquote>'
            . '<h2>The practical default</h2><ul><li>Start with RAG + strong prompting.</li><li>Fine-tune only after prompt fatigue is measurable.</li><li>Budget for evaluation either way — you cannot improve what you do not measure.</li></ul>',
        'featured'  => 0,
        'views'     => 964,
        'tags'      => ['rag', 'fine-tuning', 'ai'],
    ],
    [
        'title'     => 'Prompt Engineering Is Dead. Long Live Context Engineering.',
        'slug'      => 'context-engineering-over-prompt-engineering',
        'category'  => 'artificial-intelligence',
        'excerpt'   => 'Clever phrasing only gets you so far. The real leverage is designing what the model sees around the prompt.',
        'content'   => '<p>Crafting the perfect sentence is a fragile strategy. The durable skill is arranging everything the model reads: documents, tools, history and constraints.</p>'
            . '<h2>What counts as context</h2><p>System policy, retrieved documents, conversation memory, tool schemas, even the order of examples. All of it shapes the output.</p>'
            . '<h2>Design principles</h2><ul><li>Put constraints close to the task, not buried in a preamble.</li><li>Trim ruthlessly — irrelevant context degrades reasoning.</li><li>Structure beats prose: tables and lists are easier to follow.</li></ul>'
            . '<p>Treat context like a workspace, not a scrapbook. Clean desks produce clean work.</p>',
        'featured'  => 0,
        'views'     => 612,
        'tags'      => ['ai', 'prompting', 'llm'],
    ],
    [
        'title'     => 'The 2026 Developer Hardware Guide: What Actually Matters',
        'slug'      => 'developer-hardware-guide-2026',
        'category'  => 'technology',
        'excerpt'   => 'Cores, RAM, NPUs and battery claims decoded — buy the machine that matches your real workload.',
        'content'   => '<p>Spec sheets are marketing. Here is what changes daily comfort for people who build software.</p>'
            . '<h2>Memory is the ceiling</h2><p>Compilers, containers and browsers each want gigabytes. 16 GB is workable; 32 GB removes friction you stop noticing after a week.</p>'
            . '<h2>CPU: single-core still rules</h2><p>Most build tools and IDEs punish weak single-core speed more than they reward core count. Check generation before brand.</p>'
            . '<h2>Do you need an NPU?</h2><p>Only if you run local models daily. For everyone else it is a line item, not a reason.</p>'
            . '<blockquote>Buy for the slowest thing you do every day, not the benchmark you run once.</blockquote>',
        'featured'  => 0,
        'views'     => 508,
        'tags'      => ['hardware', 'technology', 'dev-setup'],
    ],
    [
        'title'     => 'Passkeys, End-to-End Encryption and the Passwordless Web',
        'slug'      => 'passkeys-passwordless-web',
        'category'  => 'technology',
        'excerpt'   => 'Why passkeys finally stick where SMS codes never did, and how to roll them out without losing users.',
        'content'   => '<p>Passwords failed because they asked humans to be machines. Passkeys succeed because they ask machines to be machines.</p>'
            . '<h2>How a passkey works</h2><p>A key pair is generated on your device. The private key never leaves; the public key lives on the server. Login is a cryptographic signature.</p>'
            . '<h2>Rollout advice</h2><ul><li>Offer passkeys alongside passwords, never instead of them at first.</li><li>Make recovery obvious — people lose devices, not memories.</li><li>Remember cross-device sync is the whole usability story.</li></ul>'
            . '<p>Phishing resistance comes for free: a passkey only works on the domain it was created for.</p>',
        'featured'  => 0,
        'views'     => 447,
        'tags'      => ['security', 'web', 'technology'],
    ],
    [
        'title'     => 'Cohort Analysis for Beginners: Reading Retention Like a Pro',
        'slug'      => 'cohort-analysis-retention-guide',
        'category'  => 'data-science',
        'excerpt'   => 'Blended metrics hide the truth. Cohorts show whether your product is actually keeping users.',
        'content'   => '<p>Total users always go up — even when your product is leaking. Cohort analysis stops the self-deception.</p>'
            . '<h2>What a cohort is</h2><p>Group everyone who signed up in the same week, then watch only that group over time. Repeat for the next week.</p>'
            . '<h2>Reading the table</h2><p>Rows are signup weeks, columns are weeks since signup. Strong products show warm colours extending right; weak ones fade fast.</p>'
            . '<h2>Typical diagnosis</h2><ul><li>Week-1 drop-off → onboarding problem.</li><li>Week-4 collapse → value delivery problem.</li><li>Flat line after month two → healthy core.</li></ul>'
            . '<blockquote>Acquisition without retention is a bucket with a hole.</blockquote>',
        'featured'  => 0,
        'views'     => 395,
        'tags'      => ['analytics', 'retention', 'data-science'],
    ],
    [
        'title'     => 'Feature Engineering Still Beats Bigger Models on Tabular Data',
        'slug'      => 'feature-engineering-tabular-data',
        'category'  => 'data-science',
        'excerpt'   => 'Deep learning dominates images and text — but structured tables reward domain knowledge and clean features.',
        'content'   => '<p>For tables, gradient boosting with thoughtful features consistently beats end-to-end deep networks. Here is why, and what to do about it.</p>'
            . '<h2>Structure is a gift</h2><p>Columns already carry meaning. The model just needs the right transformations: ratios, deltas, aggregations and time windows.</p>'
            . '<h2>High-impact moves</h2><ul><li>Rolling averages over raw timestamps.</li><li>Per-entity aggregates joined back to the row.</li><li>Interaction terms that encode real-world logic.</li></ul>'
            . '<h2>Validate honestly</h2><p>Random splits leak the future. Use time-based splits whenever your data has an order.</p>',
        'featured'  => 0,
        'views'     => 356,
        'tags'      => ['machine-learning', 'features', 'data-science'],
    ],
    [
        'title'     => 'Git Workflows That Scale Past Five Developers',
        'slug'      => 'git-workflows-team-scale',
        'category'  => 'programming',
        'excerpt'   => 'Trunk-based vs Git-flow, branch lifetimes and release tagging — a setup that stops merge pain.',
        'content'   => '<p>Git does not get harder with a bigger team — long-lived branches do. The fix is shortening how long work stays unmerged.</p>'
            . '<h2>Trunk-based, mostly</h2><p>Small commits to main behind feature flags. Branches live hours, not weeks, so merges stay trivial.</p>'
            . '<h2>Rules that prevent pain</h2><ul><li>Rebase your own branch freely; never rewrite shared history.</li><li>Require pull-request reviews but keep them small.</li><li>Tag releases from CI — never from a laptop.</li></ul>'
            . '<pre><code>git commit -am "feat: add cohort filter to retention view"</code></pre>'
            . '<p>When integration is continuous, releases become boring. Boring is the goal.</p>',
        'featured'  => 0,
        'views'     => 588,
        'tags'      => ['git', 'programming', 'team-work'],
    ],
    [
        'title'     => 'TypeScript Tips You Learn the Hard Way',
        'slug'      => 'typescript-tips-hard-way',
        'category'  => 'programming',
        'excerpt'   => 'Strict mode, discriminated unions and the array pitfalls that bite every newcomer.',
        'content'   => '<p>Most TypeScript pain comes from fighting the compiler instead of letting it change how you model data.</p>'
            . '<h2>Turn on strict mode</h2><p><code>strictNullChecks</code> alone eliminates a whole class of runtime surprises. Endure a couple of days of red squiggles.</p>'
            . '<h2>Model with unions</h2><p>A request is loading, success or error — never all three. Discriminated unions make the compiler enforce your state machine.</p>'
            . '<h2>Array footguns</h2><ul><li><code>Array.at(-1)</code> returns <code>T | undefined</code>.</li><li>Never index with <code>noUncheckedIndexedAccess</code> off in real projects.</li></ul>'
            . '<p>The goal is not fewer types — it is fewer production incidents.</p>',
        'featured'  => 0,
        'views'     => 474,
        'tags'      => ['typescript', 'programming', 'javascript'],
    ],
    [
        'title'     => 'How to Read a Research Paper Without Falling Asleep',
        'slug'      => 'how-to-read-research-paper',
        'category'  => 'research',
        'excerpt'   => 'A three-pass method that gets you to comprehension in a fraction of the time.',
        'content'   => '<p>Nobody reads a paper front to back. Researchers skim strategically, and so should you.</p>'
            . '<h2>Pass 1 — the five-minute scan</h2><p>Title, abstract, section headings, figures, conclusion. Decide if the paper is relevant at all.</p>'
            . '<h2>Pass 2 — the hour</h2><p>Read without proofs. Focus on claims, methods and whether the evidence supports the claims.</p>'
            . '<h2>Pass 3 — the deep dive</h2><p>Only for papers you will build on. Re-derive key results, check assumptions, note what you would do differently.</p>'
            . '<blockquote>Reading everything deeply is not diligence — it is a recipe for never finishing.</blockquote>',
        'featured'  => 0,
        'views'     => 341,
        'tags'      => ['research', 'study', 'academic'],
    ],
    [
        'title'     => 'Reproducible Research: Notebooks, Environment Pins and Data Versioning',
        'slug'      => 'reproducible-research-environment',
        'category'  => 'research',
        'excerpt'   => 'Six months later, nobody can rerun your analysis. Here is the infrastructure that fixes that.',
        'content'   => '<p>Irreproducible results are the quiet scandal of computational research. The fix is unglamorous plumbing.</p>'
            . '<h2>Pin the environment</h2><p>Lock dependency versions with a lockfile and record the interpreter version. Your laptop is not an environment specification.</p>'
            . '<h2>Version the data</h2><p>Hash raw inputs and store them somewhere immutable. Small tables can live in Git; large ones need DVC or object storage with versioning.</p>'
            . '<h2>Make one command run it all</h2><p>A single <code>make reproduce</code> or script that goes from raw data to figures. If rerunning takes more than three commands, nobody will do it.</p>',
        'featured'  => 0,
        'views'     => 289,
        'tags'      => ['research', 'reproducibility', 'data-science'],
    ],
    [
        'title'     => 'From Campus to Career: Building a Portfolio That Gets Interviews',
        'slug'      => 'student-portfolio-gets-interviews',
        'category'  => 'student-career',
        'excerpt'   => 'Recruiters spend seconds on your profile. Design a portfolio that answers their only question: can you ship?',
        'content'   => '<p>Your GPA opens doors narrowly. A portfolio proves capability in a way transcripts never can.</p>'
            . '<h2>Three projects beat ten</h2><p>Depth signals skill: a deployed project with tests, a README and real users outweighs a graveyard of tutorials.</p>'
            . '<h2>Show the reasoning</h2><p>Write about trade-offs, mistakes and what you would change. Honest engineering reflection is rare and memorable.</p>'
            . '<h2>Make it findable</h2><ul><li>Clean domain, fast load, mobile friendly.</li><li>Links to live demo and source code up front.</li><li>One page per project: problem, approach, result.</li></ul>',
        'featured'  => 0,
        'views'     => 462,
        'tags'      => ['career', 'portfolio', 'students'],
    ],
    [
        'title'     => 'Interview Prep That Works: The STAR Method and Beyond',
        'slug'      => 'interview-prep-star-method',
        'category'  => 'student-career',
        'excerpt'   => 'Structure beats rambling — a repeatable framework for behavioural and technical interviews alike.',
        'content'   => '<p>Interviews reward preparation that looks spontaneous. The trick is having a structure you can adapt under pressure.</p>'
            . '<h2>STAR, used properly</h2><p>Situation, Task, Action, Result. Most people spend 70% on context — flip it. Spend 70% on what <em>you</em> did and what changed.</p>'
            . '<h2>Build a story bank</h2><p>Prepare eight stories covering conflict, failure, leadership, ambiguity, learning, speed, quality and initiative. They answer ninety percent of behavioural questions.</p>'
            . '<h2>Technical interviews</h2><p>Think out loud, state constraints, start with the brute-force answer, then optimise. Silence is the only real failure.</p>',
        'featured'  => 0,
        'views'     => 408,
        'tags'      => ['interview', 'career', 'students'],
    ],
    [
        'title'     => 'Deep Work in a Distracted World: A Realistic System',
        'slug'      => 'deep-work-realistic-system',
        'category'  => 'productivity',
        'excerpt'   => 'Willpower is a bad scheduler. Build an environment where focus is the path of least resistance.',
        'content'   => '<p>Focus is not a personality trait — it is an environment outcome. Stop trying to become disciplined and start designing defaults.</p>'
            . '<h2>Timebox, then protect</h2><p>Two 90-minute blocks with a single task each beat eight hours of vague availability. Put them in the calendar as meetings with yourself.</p>'
            . '<h2>Make distraction expensive</h2><ul><li>Phone in another room — distance beats self-control.</li><li>Website blockers during blocks; it is not cheating.</li><li>Close chat apps; notifications are other people&rsquo;s priorities.</li></ul>'
            . '<blockquote>You do not find time for deep work. You defend it, loudly.</blockquote>'
            . '<h2>End with a shutdown</h2><p>Write tomorrow&rsquo;s single priority before you stop. Your brain lets go when it trusts the plan.</p>',
        'featured'  => 0,
        'views'     => 694,
        'tags'      => ['productivity', 'focus', 'habits'],
    ],
    [
        'title'     => 'Note-Taking for Knowledge Workers: Zettelkasten Made Simple',
        'slug'      => 'zettelkasten-note-taking-simple',
        'category'  => 'productivity',
        'excerpt'   => 'A folder of highlights is a graveyard. Link atomic notes and your archive starts thinking back.',
        'content'   => '<p>Most note systems collapse under their own weight because they optimise capture instead of retrieval.</p>'
            . '<h2>Atomic notes</h2><p>One idea per note, written in your own words. If you cannot summarise it, you have not understood it.</p>'
            . '<h2>Links over folders</h2><p>Folders force one classification. Links let ideas connect across domains — that is where originality comes from.</p>'
            . '<h2>The daily practice</h2><ul><li>Capture, then rewrite before the day ends.</li><li>Add one link to an existing note — always.</li><li>Review a random old note weekly.</li></ul>',
        'featured'  => 0,
        'views'     => 377,
        'tags'      => ['notes', 'productivity', 'pkms'],
    ],
    [
        'title'     => 'Positioning Your Freelance Niche: Charge More by Choosing Less',
        'slug'      => 'freelance-niche-positioning',
        'category'  => 'freelancing',
        'excerpt'   => 'Generalists compete on price. Specialists compete on fit — and fit is expensive for the right client.',
        'content'   => '<p>&ldquo;I do websites for everyone&rdquo; is a positioning statement that guarantees you are compared to the cheapest option available.</p>'
            . '<h2>Pick a wedge</h2><p>Industry plus outcome beats skill lists: &ldquo;checkout optimisation for DTC brands&rdquo; beats &ldquo;full-stack developer&rdquo; every time.</p>'
            . '<h2>Proof compounds</h2><p>Three case studies in one niche make the fourth client trivially easy to close. General work never accumulates like that.</p>'
            . '<h2>Raising rates</h2><p>Price rises are repositioning moments. New positioning first, new prices second — never announce a hike under the old promise.</p>',
        'featured'  => 0,
        'views'     => 533,
        'tags'      => ['freelancing', 'positioning', 'career'],
    ],
    [
        'title'     => 'Client Onboarding Emails That Prevent Scope Creep',
        'slug'      => 'client-onboarding-emails-scope-creep',
        'category'  => 'freelancing',
        'excerpt'   => 'The battle is won or lost in week one. A template sequence that sets boundaries without friction.',
        'content'   => '<p>Scope creep rarely arrives as a demand. It arrives as a friendly extra that felt too small to refuse.</p>'
            . '<h2>Email 1 — the welcome packet</h2><p>Timeline, deliverables, communication cadence, and where requests should go. Warm tone, firm content.</p>'
            . '<h2>Email 2 — the boundaries</h2><p>What is included, what counts as a change request, and the response-time window. Send this before work starts, not after the first surprise.</p>'
            . '<h2>Email 3 — the check-in</h2><p>Midpoint summary of completed vs remaining items. Alignment is cheaper than renegotiation at the deadline.</p>'
            . '<blockquote>Clarity is kindness. Vagueness is what actually damages relationships.</blockquote>',
        'featured'  => 0,
        'views'     => 319,
        'tags'      => ['freelancing', 'clients', 'communication'],
    ],
    [
        'title'     => 'Local LLMs on a Laptop: What Runs, and What It Is Actually Good For',
        'slug'      => 'local-llms-laptop-guide',
        'category'  => 'artificial-intelligence',
        'excerpt'   => 'Quantisation, context windows and honest use cases for running models offline in 2026.',
        'content'   => '<p>Local models crossed the &ldquo;genuinely useful&rdquo; line — for specific jobs. Expectations are still where the disappointment comes from.</p>'
            . '<h2>What fits on your machine</h2><p>With 16 GB of RAM, 7B–8B models at 4-bit quantisation run comfortably. Larger models need 32 GB or offloading tricks.</p>'
            . '<h2>Where local wins</h2><ul><li>Private document summarisation — nothing leaves your device.</li><li>Offline drafting and rewriting.</li><li>Structured extraction and classification at scale.</li></ul>'
            . '<h2>Where it still loses</h2><p>Complex multi-step reasoning and world knowledge. For those, frontier APIs remain worth the money.</p>',
        'featured'  => 0,
        'views'     => 726,
        'tags'      => ['ai', 'local-llm', 'hardware'],
    ],
];

$categoryMap = [];
foreach (get_categories() as $c) {
    $categoryMap[$c['slug']] = (int) $c['id'];
}

$pdo = db();

foreach ($posts as $p) {
    // Skip if slug exists
    $exists = $pdo->prepare('SELECT id FROM posts WHERE slug = ?');
    $exists->execute([$p['slug']]);
    if ($exists->fetch()) {
        echo "  skip (exists): {$p['title']}\n";
        continue;
    }

    $catId = $categoryMap[$p['category']] ?? null;
    $ins = $pdo->prepare(
        "INSERT INTO posts (title, slug, excerpt, content, author_id, category_id, status,
                            is_featured, views, reading_time, published_at)
         VALUES (?, ?, ?, ?, 1, ?, 'published', ?, ?, ?, ?)"
    );
    $ins->execute([
        $p['title'], $p['slug'], $p['excerpt'], $p['content'],
        $catId, $p['featured'], $p['views'],
        max(2, (int) ceil(str_word_count(strip_tags($p['content'])) / 220)),
        date('Y-m-d H:i:s', strtotime('-' . random_int(1, 20) . ' days')),
    ]);
    $postId = (int) $pdo->lastInsertId();

    foreach ($p['tags'] as $tagName) {
        $tagSlug = slugify($tagName);
        $tagStmt = $pdo->prepare('SELECT id FROM tags WHERE slug = ?');
        $tagStmt->execute([$tagSlug]);
        $tagId = $tagStmt->fetchColumn();
        if (!$tagId) {
            $pdo->prepare('INSERT INTO tags (name, slug) VALUES (?, ?)')->execute([$tagName, $tagSlug]);
            $tagId = $pdo->lastInsertId();
        }
        $pdo->prepare('INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)')
            ->execute([$postId, (int) $tagId]);
    }

    echo "  + {$p['title']}\n";
}

// Demo comments (mixed statuses so moderation queue looks real)
$firstPost = $pdo->query("SELECT id FROM posts WHERE slug = 'how-large-language-models-actually-work'")->fetchColumn();
if ($firstPost) {
    $has = $pdo->prepare('SELECT COUNT(*) FROM comments WHERE post_id = ?');
    $has->execute([(int) $firstPost]);
    if ((int) $has->fetchColumn() === 0) {
        $pdo->prepare("INSERT INTO comments (post_id, name, email, content, status, created_at) VALUES (?, 'Ayesha K.', 'ayesha@example.com', 'The tokenization section finally made this click for me. Great breakdown — bookmarked!', 'approved', NOW() - INTERVAL 3 DAY)")
            ->execute([(int) $firstPost]);
        $pdo->prepare("INSERT INTO comments (post_id, name, email, content, status, created_at) VALUES (?, 'Dan R.', 'dan@example.com', 'Would love a follow-up piece on fine-tuning vs RAG.', 'approved', NOW() - INTERVAL 1 DAY)")
            ->execute([(int) $firstPost]);
        echo "  + 2 demo comments\n";
    }
}

// Comments across other posts (some pending → visible in moderation queue)
$commentSeed = [
    ['sql-indexing-practical-guide', 'Maria L.', 'maria@example.com', 'The composite index order explanation is the clearest I have seen. Saved my team hours.', 'approved'],
    ['freelance-career-pricing-clients-burnout', 'Usman T.', 'usman@example.com', 'Retainers truly changed my income stability. Wish I had read this two years ago.', 'approved'],
    ['deep-work-realistic-system', 'Chloe M.', 'chloe@example.com', 'Phone in another room is criminally underrated. Tried it for a week — huge difference.', 'pending'],
    ['rag-vs-fine-tuning-llm', 'Hassan A.', 'hassan@example.com', 'How would you handle rapidly changing product catalogs with RAG?', 'pending'],
    ['evidence-based-learning-techniques', 'Fariha Z.', 'fariha@example.com', 'Interleaving felt so much harder but my mock exam scores jumped. The research checks out.', 'approved'],
    ['git-workflows-team-scale', 'Leo P.', 'leo@example.com', 'Small PRs + short-lived branches fixed our merge hell within a month.', 'pending'],
    ['context-engineering-over-prompt-engineering', 'Spam Bot', 'buy@spam.example', 'Visit my site for cheap backlinks and ranking boost!!!', 'spam'],
];
$pdoCheck = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE email = ? AND content = ?");
foreach ($commentSeed as [$postSlug, $name, $email, $content, $status]) {
    $pdoCheck->execute([$email, $content]);
    if ((int) $pdoCheck->fetchColumn() > 0) continue;
    $pid = $pdo->prepare('SELECT id FROM posts WHERE slug = ?');
    $pid->execute([$postSlug]);
    $postId = $pid->fetchColumn();
    if (!$postId) continue;
    $pdo->prepare('INSERT INTO comments (post_id, name, email, content, status, created_at) VALUES (?, ?, ?, ?, ?, NOW() - INTERVAL ? DAY)')
        ->execute([(int) $postId, $name, $email, $content, $status, random_int(1, 12)]);
    echo "  + comment ($status): {$name}\n";
}

// Contact messages for the admin inbox (insert each only if subject missing)
$msgs = [
    ['Bilal Ahmed', 'bilal@example.com', 'Guest post proposal', 'Hi, I write about embedded systems and would love to contribute a piece on ESP32 sensor networks. Happy to follow your editorial guidelines.', 3],
    ['Nadia Rahman', 'nadia@example.com', 'Collaboration inquiry', 'We run a developer newsletter with 40k subscribers and would like to republish one of your data visualization articles with full attribution.', 2],
    ['Mark Olsen', 'mark@example.com', 'Broken link on /blog', 'The tag link on the third card of page 2 seems to return a 404. Loving the content otherwise!', 1],
];
$insMsg = $pdo->prepare('INSERT INTO messages (name, email, subject, body, created_at) VALUES (?, ?, ?, ?, NOW() - INTERVAL ? DAY)');
$msgCheck = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE subject = ?');
foreach ($msgs as [$name, $email, $subject, $body, $daysAgo]) {
    $msgCheck->execute([$subject]);
    if ((int) $msgCheck->fetchColumn() > 0) continue;
    $insMsg->execute([$name, $email, $subject, $body, $daysAgo]);
    echo "  + message: {$subject}\n";
}

// A draft post + a scheduled post (so admin has varied statuses)
foreach ([
    ['The Hidden Cost of Context Switching', 'hidden-cost-context-switching', 'productivity', 'draft', 'drafts are edited in the panel', 'good'],
] as $ignore) {
    $slug = $ignore[1];
    $exists = $pdo->prepare('SELECT id FROM posts WHERE slug = ?');
    $exists->execute([$slug]);
    if ($exists->fetch()) continue;
    $catId = $categoryMap[$ignore[2]] ?? null;
    $pdo->prepare("INSERT INTO posts (title, slug, excerpt, content, author_id, category_id, status, views, reading_time, created_at, updated_at)
                   VALUES (?, ?, ?, ?, 1, ?, 'draft', 0, 4, NOW(), NOW())")
        ->execute([$ignore[0], $slug, 'A work-in-progress piece on the cognitive tax of constant task switching.', '<p>This draft is ready for editing in the admin panel — open <strong>Posts → Drafts</strong> to finish and publish it.</p>', $catId]);
    echo "  + draft post: {$ignore[0]}\n";
}

echo "Done.\n";
