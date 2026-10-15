$base = "http://localhost/Blog-website"
$phpErr = '<b>(Warning|Fatal error|Parse error|Deprecated|Notice)</b>|PHP (Warning|Fatal|Parse|Deprecated|Notice) on line|\( ! \)'

$pages = @(
  @{ u = "/";                            name = "homepage" },
  @{ u = "/blog";                        name = "blog" },
  @{ u = "/search?q=data";               name = "search" },
  @{ u = "/categories";                  name = "categories" },
  @{ u = "/category/generative-ai";      name = "subcat generative-ai" },
  @{ u = "/category/artificial-intelligence"; name = "cat AI" },
  @{ u = "/category/cloud-computing";    name = "cat cloud" },
  @{ u = "/post/how-large-language-models-actually-work"; name = "post LLM(diagram cover)" },
  @{ u = "/post/serverless-architecture-when-it-shines-and-when-it-fails"; name = "post serverless(photo)" },
  @{ u = "/author/abd-ur-rahman";        name = "author" },
  @{ u = "/about";                       name = "about" },
  @{ u = "/contact";                     name = "contact" },
  @{ u = "/sitemap.xml";                 name = "sitemap" },
  @{ u = "/definitely-missing-page";     name = "404" }
)

$fail = 0
foreach ($p in $pages) {
  $url = $base + $p.u
  $code = 0; $html = ""
  # curl.exe instead of Invoke-WebRequest: PS 5.1's .NET client mishandles
  # chunked non-2xx responses ("connection closed unexpectedly") — the
  # pages themselves are fine (full body + proper status).
  $tmp = Join-Path $env:TEMP 'sweep_front_page.html'
  $raw = curl.exe -s -o $tmp -w "%{http_code}" --max-time 30 $url
  if ($raw -match '^\d+$') { $code = [int]$raw }
  $html = Get-Content $tmp -Raw -ErrorAction SilentlyContinue
  if ($null -eq $html) { $html = "" }
  $errs = [regex]::Matches($html, $phpErr)
  $expCode = if ($p.name -eq "404") { 404 } else { 200 }
  $ok = ($code -eq $expCode -and $errs.Count -eq 0)
  if (-not $ok) { $fail++ }
  $status = if ($ok) { "OK  " } else { "FAIL" }
  $errTxt = if ($errs.Count) { " PHP-ERR x$($errs.Count): " + (($errs | ForEach-Object { $_.Value }) -join ' | ') } else { "" }
  "$status [$code] $($p.name)$errTxt"

  # ---- element assertions ----
  $checks = @()
  if ($p.name -eq "homepage") {
    $checks += ,@("navbar logo img", ($html -match 'assets/uploads/logo\.png'))
    $checks += ,@("favicon logo-icon", ($html -match 'assets/uploads/logo-icon\.png'))
    # footer may be any design version — require the logo <img> inside <footer>
    $footerSec = [regex]::Match($html, '<footer[\s\S]*?</footer>').Value
    $checks += ,@("footer logo img", ($footerSec -match 'assets/uploads/logo[^"]*"[^>]*>' -or ($footerSec -match '<img[^>]*uploads/logo')))
    $topics = [regex]::Matches($html, '/assets/img/(topics|diagrams)/[^"]+')
    $checks += ,@("card images = $($topics.Count)", ($topics.Count -ge 3))
    $checks += ,@("no gradient covers", (-not ($html -match 'placeholder_image|src="data:image/svg\+xml;base64,[^"]{5000,}')))
  }
  if ($p.name -like "post *") {
    $checks += ,@("author avatar photo", ($html -match 'avatar-1-b78f2\.png'))
    $checks += ,@("NO double uploads path", (-not ($html -match 'uploads/assets/uploads')))
    $checks += ,@("cover img (topic/diagram)", ($html -match '/assets/img/(topics|diagrams)/[^"]+'))
    $checks += ,@("breadcrumb", ($html -match 'Breadcrumb'))
  }
  if ($p.name -like "cat *" -or $p.name -like "subcat *") {
    $n = [regex]::Matches($html, '/assets/img/(topics|diagrams)/[^"]+').Count
    $checks += ,@("category images = $n", ($n -ge 1))
  }
  if ($p.name -eq "author") {
    $checks += ,@("author big avatar photo", ($html -match 'avatar-1-b78f2\.png'))
    $checks += ,@("NO double uploads path", (-not ($html -match 'uploads/assets/uploads')))
  }
  foreach ($c in $checks) {
    if ($c[1]) { "     + $($c[0])" } else { "     - MISSING: $($c[0])"; $script:fail++ }
  }
}
""
if ($fail -eq 0) { "FRONTEND SWEEP: ALL PASS" } else { "FRONTEND SWEEP: $fail FAILURE(S)" }
