<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1>Privacy policy</h1>
<p><small>Last updated <?= esc($updated) ?></small></p>
<p>This policy explains what <?= esc($biz['name']) ?> collects when you use our website, and what we do with it.</p>
<h2>What we collect</h2>
<p>Your name and email address when you create an account. The content you put into an invitation: names, dates, venues, photos and messages. Replies that your guests send through your invitation. If you import a guest list, the names, phone numbers and email addresses you add. Payment details are entered on our payment provider's page and are not stored by us. We keep the order amount, the status and the provider's reference.</p>
<h2>How we use it</h2>
<p>To build, publish and show your invitation, to take payment, to email you about your order and to reset your password, and to keep the service secure. We do not sell your data.</p>
<h2>Who sees your invitation</h2>
<p>Anyone with your invitation link can see it. Do not put anything in an invitation that you would not share with your guests. Guest lists and replies are visible only to you.</p>
<h2>Service providers</h2>
<p>We use a payment provider (Razorpay, and others in future), an email service, a web host, and Google Fonts to display text. These providers process data only to provide their service to us.</p>
<h2>Cookies</h2>
<p>We use cookies that are needed for signing in, for your language and for your basket. We do not use advertising cookies.</p>
<h2>Keeping and deleting data</h2>
<p>We keep your account and invitations until you ask us to delete them. Email <?= esc($biz['email']) ?> to request a copy or deletion of your data. Order records may be kept as long as the law requires.</p>
<h2>Children</h2>
<p>Our service is for adults. Invitations may mention children, such as a birthday child, but the account holder must be an adult.</p>
<h2>Contact</h2>
<p><?= esc($biz['email']) ?></p>
<?= $this->endSection() ?>
