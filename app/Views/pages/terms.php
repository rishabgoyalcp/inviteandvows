<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h1>Terms of service</h1>
<p><small>Last updated <?= esc($updated) ?></small></p>
<p>By using this website you agree to these terms with <?= esc($biz['name']) ?>.</p>
<h2>What we provide</h2>
<p>Digital invitations: a web page invitation, a video invitation, and editable Canva designs. Prices are shown before you pay and include the taxes stated at checkout.</p>
<h2>Your content</h2>
<p>You own what you put into an invitation. You allow us to store and show it so the service works. You must have the right to use all text and photos, and you must not upload anything unlawful, hateful or that infringes anyone's rights. We may remove content that breaks these rules.</p>
<h2>Canva designs</h2>
<p>An editable Canva design is a licence to edit and use the design for your own event. You may not resell or redistribute the design or the link.</p>
<h2>Availability</h2>
<p>Published invitations stay online for the period shown at purchase. If none is shown, we keep them for at least 12 months. We aim for the service to be always available but cannot promise it.</p>
<h2>Liability</h2>
<p>We are not responsible for losses that come from wrong information in your invitation, from guests who do not see a message, or from events outside our control. Our total responsibility for any order is limited to the amount you paid for it.</p>
<h2>Changes and law</h2>
<p>We may update these terms and will show the date above. These terms are governed by the laws of India, and the courts of our registered place of business have jurisdiction.</p>
<h2>Contact</h2>
<p><?= esc($biz['email']) ?></p>
<?= $this->endSection() ?>
