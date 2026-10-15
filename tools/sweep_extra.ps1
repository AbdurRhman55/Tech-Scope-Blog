$base = "http://localhost/Blog-website"
$fail = 0
function Check($cond, $label) {
  if ($cond) { "     + $label" } else { "     - MISSING: $label"; $script:fail++ }
}

# 1) /categories — every category card must have a real image
$r = Invoke-WebRequest "$base/categories" -UseBasicParsing -TimeoutSec 30
$n = [regex]::Matches($r.Content, '/assets/img/(topics|diagrams)/[^"]+').Count
Check ($n -ge 8) "categories page images = $n"
Check ($r.Content -notmatch 'src="data:image/svg\+xml;base64,[^"]{5000,}') "no gradient category covers"

# 2) diagram-cover post: JSON-LD publisher logo = site logo, og:image = default png (not svg)
$r2 = Invoke-WebRequest "$base/post/how-large-language-models-actually-work" -UseBasicParsing -TimeoutSec 30
Check ($r2.Content -match '"logo"\s*:\s*"http://localhost/Blog-website/assets/uploads/logo\.png"') "JSON-LD publisher logo = uploaded logo"
$og = [regex]::Match($r2.Content, 'property="og:image"\s+content="([^"]+)"').Groups[1].Value
Check ($og -notmatch '\.svg') "og:image not svg -> $og"

# 3) photo-cover post: og:image = photo
$r3 = Invoke-WebRequest "$base/post/serverless-architecture-when-it-shines-and-when-it-fails" -UseBasicParsing -TimeoutSec 30
$og3 = [regex]::Match($r3.Content, 'property="og:image"\s+content="([^"]+)"').Groups[1].Value
Check ($og3 -match 'topics/cloud-computing') "og:image = photo -> $og3"

# 4) homepage hero/latest — featured post image + og of homepage
$r4 = Invoke-WebRequest "$base/" -UseBasicParsing -TimeoutSec 30
Check ($r4.Content -match 'assets/uploads/logo\.png[^>]*fetchpriority|fetchpriority="high"') "homepage logo fetchpriority"

# 5) sitemap contains subcategory URLs
$r5 = Invoke-WebRequest "$base/sitemap.xml" -UseBasicParsing -TimeoutSec 30
Check ($r5.Content -match 'category/generative-ai') "sitemap has subcategory URLs"

""
if ($fail -eq 0) { "EXTRA CHECKS: ALL PASS" } else { "EXTRA CHECKS: $fail FAILURE(S)" }
