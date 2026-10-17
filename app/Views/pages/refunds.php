<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1>Refund and cancellation policy</h1>
<p><small>Last updated <?= esc($updated) ?></small></p>
<p>Our products are digital and are delivered right after payment, so we handle refunds as follows.</p>
<h2>When you can get a refund</h2>
<ul>
<li>You were charged twice for the same order.</li>
<li>You paid but the invitation was not published, or the Canva link did not work, and we could not fix it within 3 working days.</li>
<li>The product is materially different from what was shown on its page.</li>
</ul>
<h2>When we cannot refund</h2>
<p>After you have published an invitation, received a Canva link, or created a video, because the product has been delivered. Changing your mind or a change of event date is not a reason for a refund, but you can edit your invitation at any time.</p>
<h2>How to ask</h2>
<p>Email <?= esc($biz['email']) ?> within 7 days of the payment with your order number. Approved refunds go back to the original payment method and normally reach you within 5 to 10 working days.</p>
<?= $this->endSection() ?>
