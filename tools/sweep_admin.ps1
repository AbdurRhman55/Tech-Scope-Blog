$base = "http://localhost/Blog-website"
$phpErr = '<b>(Warning|Fatal error|Parse error|Deprecated|Notice)</b>|PHP (Warning|Fatal|Parse|Deprecated|Notice) on line|\( ! \)'
$fail = 0

function Check($cond, $label) {
  if ($cond) { "     + $label" } else { "     - MISSING: $label"; $script:fail++ }
}

# ---- login ----
$sess = $null
$r1 = Invoke-WebRequest "$base/admin/login.php" -UseBasicParsing -SessionVariable sess -TimeoutSec 30
$token = [regex]::Match($r1.Content, 'name="csrf_token" value="([^"]+)"').Groups[1].Value
$loginCode = "?"
try {
  $r2 = Invoke-WebRequest "$base/admin/login.php" -Method Post -Body @{ email = 'admin@example.com'; password = 'Admin@123'; csrf_token = $token } -WebSession $sess -UseBasicParsing -MaximumRedirection 0 -TimeoutSec 30
  $loginCode = "$($r2.StatusCode)"
} catch {
  if ($_.Exception.Response) { $loginCode = "$([int]$_.Exception.Response.StatusCode)" } else { $loginCode = "ERR $($_.Exception.Message)" }
}
$loginOk = ($loginCode -eq "302" -or $loginCode -eq "200")
if ($loginOk) { "OK   login -> $loginCode" } else { "FAIL login -> $loginCode"; $fail++ }

# ---- pages ----
$pages = @(
  @{ u = "/admin/index.php";      name = "dashboard" },
  @{ u = "/admin/posts.php";      name = "posts" },
  @{ u = "/admin/post-edit.php?id=1"; name = "post-edit id=1" },
  @{ u = "/admin/post-edit.php";  name = "post-edit new" },
  @{ u = "/admin/categories.php"; name = "categories" },
  @{ u = "/admin/users.php";      name = "users" },
  @{ u = "/admin/comments.php";   name = "comments" },
  @{ u = "/admin/settings.php";   name = "settings" },
  @{ u = "/admin/profile.php";    name = "profile" }
)

foreach ($p in $pages) {
  $code = 0; $html = ""
  try {
    $r = Invoke-WebRequest ($base + $p.u) -UseBasicParsing -WebSession $sess -TimeoutSec 30
    $code = $r.StatusCode; $html = $r.Content
  } catch {
    if ($_.Exception.Response) { $code = [int]$_.Exception.Response.StatusCode } else { $code = 0 }
    try { $sr = New-Object IO.StreamReader($_.Exception.Response.GetResponseStream()); $html = $sr.ReadToEnd(); $sr.Close() } catch {}
  }
  $errs = [regex]::Matches($html, $phpErr)
  $ok = ($code -eq 200 -and $errs.Count -eq 0)
  if (-not $ok) { $fail++ }
  $errTxt = if ($errs.Count) { " PHP-ERR x$($errs.Count): " + (($errs | ForEach-Object { $_.Value }) -join ' | ') } else { "" }
  if ($ok) { "OK   [200] $($p.name)" } else { "FAIL [$code] $($p.name)$errTxt" }

  if ($p.name -eq "dashboard") {
    Check ($html -match 'assets/uploads/logo\.png') "sidebar brand logo img"
    $av = [regex]::Matches($html, 'assets/uploads/avatar-1-b78f2\.png').Count
    Check ($av -ge 2) "avatar photo on sidebar+topbar (found $av)"
    Check ($html -notmatch 'uploads/assets/uploads') "NO double uploads path"
  }
  if ($p.name -eq "post-edit id=1") {
    Check ($html -match 'id="cover-preview"[^>]*src="[^"]*diagrams/how-large-language-models-actually-work-llm-flow\.svg"') "cover preview = diagram URL"
    Check ($html -match 'id="cover-input"[^>]*value="assets/img/diagrams/') "cover input raw value"
    Check ($html -match 'mt-count') "SEO meta counters"
    Check ($html -match '\+ New category') "inline category creator"
    Check ($html -match 'Google preview') "SERP preview (Google preview)"
  }
  if ($p.name -eq "users") {
    Check ($html -match '<img src="[^"]*avatar-1-b78f2\.png"[^>]*class="h-9 w-9') "users list shows avatar photo"
  }
  if ($p.name -eq "settings") {
    Check ($html -match 'logo\.png') "logo setting shows uploaded logo.png"
  }
  if ($p.name -eq "profile") {
    Check ($html -match 'avatar-1-b78f2\.png') "profile photo shown"
  }
}

# ---- login page (fresh session, no auth) favicon ----
try {
  $rl = Invoke-WebRequest "$base/admin/login.php" -UseBasicParsing -TimeoutSec 30
  Check ($rl.Content -match 'assets/uploads/logo-icon\.png') "login page favicon = logo-icon.png"
} catch { "FAIL login page fetch"; $fail++ }

""
if ($fail -eq 0) { "ADMIN SWEEP: ALL PASS" } else { "ADMIN SWEEP: $fail FAILURE(S)" }
