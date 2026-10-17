#!/usr/bin/env bash
# End-to-end smoke test for a running copy of the site. It needs curl and a server in the development
# environment (the fake payment button only exists there). It creates throw-away users, a coupon, an invite and a blog post.
#
#   php spark serve --port 8080 &
#   BASE=http://localhost:8080 ADMIN_EMAIL=admin@example.com ADMIN_PASS=ChangeMe123 tests/smoke.sh
#
# Optional: TEMPLATE=classic-birthday (slug), TEMPLATE_ID=2 and REGION_ID=1 (used to set up a Canva product).
set -u
BASE=${BASE:-http://localhost:8080}
ADMIN_EMAIL=${ADMIN_EMAIL:-admin@example.com}
ADMIN_PASS=${ADMIN_PASS:-ChangeMe123}
TEMPLATE=${TEMPLATE:-classic-birthday}
TEMPLATE_ID=${TEMPLATE_ID:-2}
REGION_ID=${REGION_ID:-1}
RUN=$(date +%s)$RANDOM
DIR=$(mktemp -d); trap 'rm -rf "$DIR"' EXIT
PASS=0; FAIL=0

ok()  { PASS=$((PASS + 1)); printf '  ok    %s\n' "$1"; }
bad() { FAIL=$((FAIL + 1)); printf '  FAIL  %s\n' "$1"; }
check() { if eval "$2"; then ok "$1"; else bad "$1"; fi; }
tok() { grep -oE 'name="csrf_test_name"[^>]*value="[^"]+"|value="[^"]+"[^>]*name="csrf_test_name"' | head -1 | grep -oE 'value="[^"]+"' | cut -d'"' -f2; }
code() { curl -s -o /dev/null -w '%{http_code}' "$@"; }
# usage: j=<cookie jar>; req $j args...
req() { local j=$1; shift; curl -s -c "$j" -b "$j" "$@"; }

echo "Public pages"
for p in "" templates "templates/$TEMPLATE" hi blog tools/budget sitemap.xml robots.txt login; do
  check "GET /$p is 200" "[ \"\$(code $BASE/$p)\" = 200 ]"
done
check "unknown page is 404" "[ \"\$(code $BASE/definitely-not-here)\" = 404 ]"
check "unknown language prefix is 404" "[ \"\$(code $BASE/zz/templates)\" = 404 ]"

echo "Access control"
check "anonymous /dashboard redirects" "[ \"\$(code $BASE/dashboard)\" = 302 ]"
check "anonymous /admin redirects" "[ \"\$(code $BASE/admin)\" = 302 ]"
check "post without CSRF token is refused" "[ \"\$(code -d x=1 $BASE/login)\" = 403 ]"

echo "Admin"
A=$DIR/admin.jar
T=$(req $A $BASE/login | tok)
req $A -o /dev/null -d "csrf_test_name=$T&email=$ADMIN_EMAIL&password=$ADMIN_PASS" $BASE/login
for p in admin admin/event-types admin/templates admin/languages admin/coupons admin/blog admin/templates/$TEMPLATE_ID/design; do
  check "admin page /$p" "[ \"\$(req $A -o /dev/null -w '%{http_code}' $BASE/$p)\" = 200 ]"
done
CP=SMOKE$RUN
T=$(req $A $BASE/admin/coupons | tok)
req $A -o /dev/null -d "csrf_test_name=$T&id=0&code=$CP&type=percent&value=50" $BASE/admin/coupons
check "coupon $CP created" "req $A $BASE/admin/coupons | grep -q $CP"
# a Canva product on the test template, in the test region
T=$(req $A $BASE/admin/templates | tok)
req $A -o /dev/null -d "csrf_test_name=$T&id=$TEMPLATE_ID&view_file=templates/classic&is_active=1&price[$REGION_ID][website]=1499&price[$REGION_ID][canva_design]=499&url[$REGION_ID][canva_design]=https://www.canva.com/design/SMOKE$RUN/use-template" $BASE/admin/templates
req $A -o /dev/null -d "csrf_test_name=$T&id=0&locale=en&slug=smoke-$RUN&title=Smoke post $RUN&body=Hello **world**&is_published=1" $BASE/admin/blog
check "blog post is public" "curl -s $BASE/blog | grep -q smoke-$RUN"

echo "Customer: build, coupon, pay, publish"
C=$DIR/cust.jar; EMAIL=smoke$RUN@example.com
T=$(req $C $BASE/templates/$TEMPLATE | tok)
LOC=$(req $C -o /dev/null -w '%{redirect_url}' -d "csrf_test_name=$T&format=website" $BASE/build/start/$TEMPLATE); TK=${LOC##*/}
check "builder started" "[ -n \"$TK\" ] && [ \"$TK\" != \"\" ]"
HT=$(req $C $BASE/build/$TK | grep -oE 'csrf = "[^"]+"' | grep -oE '"[^"]+"' | tr -d '"')
check "save draft" "req $C -H 'X-CSRF-TOKEN: $HT' -d 'person_name=Smoke&event_date=2026-12-01' $BASE/build/$TK/save | grep -q '\"ok\"'"
check "live preview renders" "req $C -H 'X-CSRF-TOKEN: $HT' -d 'person_name=Previewed' $BASE/build/$TK/preview | grep -q Previewed"
T=$(req $C $BASE/checkout/$TK | tok)
req $C -o /dev/null -d "csrf_test_name=$T&name=Smoke&email=$EMAIL&password=secret1234" $BASE/checkout/$TK/account
S=$(req $C $BASE/checkout/$TK); T=$(echo "$S" | tok)
printf '%s' "$S" > $DIR/summary.html
check "summary shows a price" "grep -qE '[₹$][0-9]' $DIR/summary.html"
S=$(req $C -d "csrf_test_name=$T&coupon=$CP" $BASE/checkout/$TK/pay); REF=$(echo "$S" | grep -oE 'sim_[a-f0-9]+' | head -1); T=$(echo "$S" | tok)
check "payment page offered (dev simulation)" "[ -n '$REF' ]"
req $C -o /dev/null -d "csrf_test_name=$T&ref=$REF" $BASE/checkout/$TK/simulate
D=$(req $C $BASE/dashboard)
printf '%s' "$D" > $DIR/dash.html
check "invite is published" "grep -q published $DIR/dash.html"
URL=$(echo "$D" | grep -oE 'https?(:|&#x3A;)(//|&#x2F;&#x2F;)[^"<]*(i|wd2\.in)(/|&#x2F;)[A-Za-z0-9]{7}' | head -1 | sed 's/&#x3A;/:/g;s/&#x2F;/\//g')
INV_CODE=${URL##*/}
check "public invite page loads" "[ \"\$(code $BASE/i/$INV_CODE)\" = 200 ]"
P=$(req $C $BASE/i/$INV_CODE); T=$(echo "$P" | tok)
check "RSVP accepted" "[ \"\$(req $C -o /dev/null -w '%{http_code}' -d \"csrf_test_name=$T&guest_name=Guest&status=yes&guests=2\" $BASE/i/$INV_CODE/rsvp)\" = 303 ]"
INV_ID=$(echo "$D" | grep -oE 'invites(/|&#x2F;)[0-9]+(/|&#x2F;)guests' | head -1 | grep -oE '[0-9]+')
check "RSVP list shows the reply" "req $C $BASE/dashboard/invites/$INV_ID/rsvps | grep -q Guest"
check "RSVP CSV downloads" "[ \"\$(req $C -o /dev/null -w '%{http_code}' '$BASE/dashboard/invites/$INV_ID/rsvps?csv=1')\" = 200 ]"

echo "Customer: guest list"
T=$(req $C $BASE/dashboard/invites/$INV_ID/guests | tok)
req $C -o /dev/null -F csrf_test_name=$T -F cc=91 -F 'list=Asha Sharma, 98765 43210' $BASE/dashboard/invites/$INV_ID/guests/import
G=$(req $C $BASE/dashboard/invites/$INV_ID/guests)
GT=$(echo "$G" | grep -oE 'g(=|&#x3D;)[a-f0-9]{16}' | head -1 | grep -oE '[a-f0-9]{16}$')
check "guest imported with a personal link" "[ -n '$GT' ]"
check "personal link greets the guest" "curl -s '$BASE/i/$INV_CODE?g=$GT' | grep -q 'Dear Asha'"
check "other people cannot see the guest list" "[ \"\$(code $BASE/dashboard/invites/$INV_ID/guests)\" = 302 ]"

echo "Customer: Canva product"
CB=$DIR/buyer.jar
BUY=$(curl -s $BASE/templates/$TEMPLATE | grep -oE '(buy|buy&#x2F;)[0-9]+|/buy/[0-9]+' | grep -oE '[0-9]+' | head -1)
check "Canva buy button listed" "[ -n '$BUY' ]"
T=$(req $CB $BASE/buy/$BUY | tok)
req $CB -o /dev/null -d "csrf_test_name=$T&name=Buyer&email=buyer$RUN@example.com&password=secret1234" $BASE/buy/$BUY/account
S=$(req $CB $BASE/buy/$BUY); T=$(echo "$S" | tok)
S=$(req $CB -d "csrf_test_name=$T&coupon=" $BASE/buy/$BUY); REF=$(echo "$S" | grep -oE 'sim_[a-f0-9]+' | head -1); T=$(echo "$S" | tok)
ORD=$(echo "$S" | grep -oE 'buy(/|&#x2F;)order(/|&#x2F;)[0-9]+' | grep -oE '[0-9]+$' | head -1)
req $CB -o /dev/null -d "csrf_test_name=$T&ref=$REF" $BASE/buy/order/$ORD/simulate
check "purchase listed" "req $CB $BASE/dashboard | grep -q 'Canva'"
check "link hidden before opening" "! req $CB $BASE/dashboard | grep -q canva.com/design"
check "paid customer is sent to Canva" "[ \"\$(req $CB -o /dev/null -w '%{redirect_url}' $BASE/dashboard/orders/$ORD/open | grep -c canva.com)\" = 1 ]"
check "someone else cannot open that order" "[ \"\$(req $C -o /dev/null -w '%{http_code}' $BASE/dashboard/orders/$ORD/open)\" = 404 ]"

echo "Referrals and uploads"
check "referral link is shown" "req $C $BASE/dashboard | grep -q 'r&#x2F;'"
head -c 4000 /dev/urandom > $DIR/fake.mp4
CS=$(awk '/csrf_cookie_name/{print $7}' $C | head -1)
check "fake video upload refused" "[ \"\$(req $C -o /dev/null -w '%{http_code}' -H \"X-CSRF-TOKEN: $CS\" -F video=@$DIR/fake.mp4 $BASE/dashboard/invites/$INV_ID/video)\" != 200 ]"

echo
echo "Passed: $PASS   Failed: $FAIL"
[ "$FAIL" = 0 ]
