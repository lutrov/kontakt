# Kontakt

Kontakt is a simple contact form that allows you to capture a name, email, telephone, company and message. No fancy form builder, no advanced conditional logic, just the basics. Allows you to block spambots without using annoying captchas and optionally stores messages as private custom post types in the database. Why this plugin name? Kontakt means "contact" in Polish.

## Professional Support

If you need professional plugin support from me, the plugin author, contact me via my website at http://lutrov.com

## Copyright and License

This project is licensed under the [GNU GPL](http://www.gnu.org/licenses/old-licenses/gpl-2.0.html), version 2 or later.

## Documentation

Kontakt uses a shortcode which can be used as a Gutenberg block or placed in a sidebar widget. Here's a usage example:

```
[kontakt form="1234" fields="name|email|telephone|company|message|agreement" required="name|email|telephone|company|message|agreement" subject="Contact form" to="admin@example.com" cc="hello@example.com" bcc="bye@example.com" agreement="terms" redirect="thanks" quiz="What is 4+8? 12" anchor="content"]
```

OR

```
[contact form="1234" fields="name|email|telephone|company|message|agreement" required="name|email|telephone|company|message|agreement" subject="Contact form" to="admin@example.com" cc="hello@example.com" bcc="bye@example.com" agreement="terms" redirect="thanks" quiz="What is 4+8? 12" anchor="content"]
```

Breaking down the shortcode attributes:

* `form`: This is the form id and can be a numeric, alphabetic or alphanumeric string which identifies the form.
* `fields`: This is a list of fields in use and would be one or more of name, email, telephone, company, message & agreement.
* `required`: This is a list specifying which fields are required.
* `subject`: This is the optional subject, and if not specified defaults to "Contact form".
* `to`: This is the optional "to" address, and if not specified defaults to your site's admin email address.
* `cc`: This is the optional "cc" address.
* `bcc`: This is the optional "bcc" address.
* `quiz`: This is the optional quiz question and answer challenge, delimited by a question mark.
* `agreement`: This is the slug of the page path which has the terms of use or the privacy policy, which is used when `agreement` is specified in the list of fields.
* `redirect`: This is the optional slug of the page path to redirect to after successful submission.
* `anchor`: This is the optional section on the page to anchor the form to after submission.

Please note that `agreement` must be a valid terms and conditions or privacy policy slug, otherwise the agreement (checkbox) field won't show. Also, please note that it makes no sense to specify both `redirect` and `anchor` since they're mutually exclusive.

Also, fields will always show in this order:

1. `name`
2. `email`
3. `telephone`
4. `company`
5. `message`
6. `quiz`
7. `agreement`

This plugin provides an API to customise the default field labels and messages. See these examples:

```
// ---- Customise contact form shortcode name field label.
add_filter('kontakt_shortcode_name_label', 'custom_kontakt_shortcode_name_label_filter', 10, 2);
function custom_kontakt_shortcode_name_label_filter($label, $form_id) {
	if ($form_id == '1234') {
		$label = __('Name');
	}
	return $label;
}

// ---- Customise contact form shortcode name field empty message.
add_filter('kontakt_shortcode_name_empty', 'custom_kontakt_shortcode_name_empty_filter', 10, 2);
function custom_kontakt_shortcode_name_empty_filter($message, $form_id) {
	if ($form_id == '1234') {
		$message = __('Name is required.');
	}
	return $message;
}

// ---- Customise contact form shortcode name field invalid message.
add_filter('kontakt_shortcode_name_invalid', 'custom_kontakt_shortcode_name_invalid_filter', 10, 2);
function custom_kontakt_shortcode_name_invalid_filter($message, $form_id) {
	if ($form_id == '1234') {
		$message = __('Invalid name.');
	}
	return $message;
}

// ---- Customise contact form shortcode email field label.
add_filter('kontakt_shortcode_email_label', 'custom_kontakt_shortcode_email_label_filter', 10, 2);
function custom_kontakt_shortcode_email_label_filter($label, $form_id) {
	if ($form_id == '1234') {
		$label = __('Email');
	}
	return $label;
}

// ---- Customise contact form shortcode email field empty message.
add_filter('kontakt_shortcode_email_empty', 'custom_kontakt_shortcode_email_empty_filter', 10, 2);
function custom_kontakt_shortcode_email_empty_filter($message, $form_id) {
	if ($form_id == '1234') {
		$message = __('Email is required.');
	}
	return $message;
}

// ---- Customise contact form shortcode email field invalid message.
add_filter('kontakt_shortcode_email_invalid', 'custom_kontakt_shortcode_email_invalid_filter', 10, 2);
function custom_kontakt_shortcode_email_invalid_filter($message, $form_id) {
	if ($form_id == '1234') {
		$message = __('Invalid email.');
	}
	return $message;
}

// ---- Customise contact form shortcode telephone field label.
add_filter('kontakt_shortcode_telephone_label', 'custom_kontakt_shortcode_telephone_label_filter', 10, 2);
function custom_kontakt_shortcode_telephone_label_filter($label, $form_id) {
	if ($form_id == '1234') {
		$label = __('Telephone');
	}
	return $label;
}

// ---- Customise contact form shortcode telephone field empty message.
add_filter('kontakt_shortcode_telephone_empty', 'custom_kontakt_shortcode_telephone_empty_filter', 10, 2);
function custom_kontakt_shortcode_telephone_empty_filter($message, $form_id) {
	if ($form_id == '1234') {
		$message = __('Telephone is required.');
	}
	return $message;
}

// ---- Customise contact form shortcode telephone field invalid message.
add_filter('kontakt_shortcode_telephone_invalid', 'custom_kontakt_shortcode_telephone_invalid_filter', 10, 2);
function custom_kontakt_shortcode_telephone_invalid_filter($message, $form_id) {
	if ($form_id == '1234') {
		$message = __('Invalid telephone.');
	}
	return $message;
}

// ---- Customise contact form shortcode company field label.
add_filter('kontakt_shortcode_company_label', 'custom_kontakt_shortcode_company_label_filter', 10, 2);
function custom_kontakt_shortcode_company_label_filter($label, $form_id) {
	if ($form_id == '1234') {
		$label = __('Company');
	}
	return $label;
}

// ---- Customise contact form shortcode company field empty message.
add_filter('kontakt_shortcode_company_empty', 'custom_kontakt_shortcode_company_empty_filter', 10, 2);
function custom_kontakt_shortcode_company_empty_filter($message, $form_id) {
	if ($form_id == '1234') {
		$message = __('Company is required.');
	}
	return $message;
}

// ---- Customise contact form shortcode message field label.
add_filter('kontakt_shortcode_message_label', 'custom_kontakt_shortcode_message_label_filter', 10, 2);
function custom_kontakt_shortcode_message_label_filter($label, $form_id) {
	if ($form_id == '1234') {
		$label = __('Message');
	}
	return $label;
}

// ---- Customise contact form shortcode message field empty message.
add_filter('kontakt_shortcode_message_empty', 'custom_kontakt_shortcode_message_empty_filter', 10, 2);
function custom_kontakt_shortcode_message_empty_filter($message, $form_id) {
	if ($form_id == '1234') {
		$message = __('Message is required.');
	}
	return $message;
}

// ---- Customise contact form shortcode agreement field label.
add_filter('kontakt_shortcode_agreement_label', 'custom_kontakt_shortcode_agreement_label_filter', 10, 2);
function custom_kontakt_shortcode_agreement_label_filter($label, $form_id) {
	if ($form_id == '1234') {
		$label = __('I agree to the terms and conditions set out on this site.');
	}
	return $label;
}

// ---- Customise contact form shortcode agreement field empty message.
add_filter('kontakt_shortcode_agreement_empty', 'custom_kontakt_shortcode_agreement_empty_filter', 10, 2);
function custom_kontakt_shortcode_agreement_empty_filter($message, $form_id) {
	if ($form_id == '1234') {
		$message = __('Agreement is required.');
	}
	return $message;
}

// ---- Customise contact form shortcode submit button label.
add_filter('kontakt_shortcode_submit_label', 'custom_kontakt_shortcode_submit_label_filter', 10, 2);
function custom_kontakt_shortcode_submit_label_filter($label, $form_id) {
	if ($form_id == '1234') {
		$label = __('Send');
	}
	return $label;
}

// ---- Customise contact form shortcode submit error message.
add_filter('kontakt_shortcode_message_submit_error', 'custom_kontakt_shortcode_message_submit_error_filter', 10, 2);
function custom_kontakt_shortcode_message_submit_error_filter($message, $form_id) {
	if ($form_id == '1234') {
		$message = __('One or more fields have an error, please check and try again.');
	}
	return $message;
}

// ---- Customise contact form shortcode spam error message.
add_filter('kontakt_shortcode_message_spam_error', 'custom_kontakt_shortcode_message_spam_error_filter', 10, 2);
function custom_kontakt_shortcode_message_spam_error_filter($message, $form_id) {
	if ($form_id == '1234') {
		$message = __('There was an error while sending your message, please try again later.');
	}
	return $message;
}

// ---- Customise contact form shortcode submit success message.
add_filter('kontakt_shortcode_message_success', 'custom_kontakt_shortcode_message_success_filter', 10, 2);
function custom_kontakt_shortcode_message_success_filter($message, $form_id) {
	if ($form_id == '1234') {
		$message = __('Your message has been sent.');
	}
	return $message;
}

// ---- Customise contact form shortcode submit technical error message.
add_filter('kontakt_shortcode_message_tech_error', 'custom_kontakt_shortcode_message_tech_error_filter', 10, 2);
function custom_kontakt_shortcode_message_tech_error_filter($message, $form_id) {
	if ($form_id == '1234') {
		$message = __('There was an error while sending your message, please try again later.');
	}
	return $message;
}
```

You can customise the way the contact form looks by adding something like this to your custom stylesheet file:

```
.wp-block-kontakt-form {
}

.wp-block-kontakt-form label {
	display: block;
	margin-top: 10px;
	margin-bottom: 10px;
}

.wp-block-kontakt-form label span.required {
	color: red;
}

.wp-block-kontakt-form input[type="text"],
.wp-block-kontakt-form input[type="email"],
.wp-block-kontakt-form input[type="tel"],
.wp-block-kontakt-form textarea {
	border: 2px solid black;
	outline: none;
}

.wp-block-kontakt-form input[type="text"].error,
.wp-block-kontakt-form input[type="email"].error,
.wp-block-kontakt-form input[type="tel"].error,
.wp-block-kontakt-form textarea.error {
	border-color: red;
}

.wp-block-kontakt-form input[type="text"]:focus,
.wp-block-kontakt-form input[type="email"]:focus,
.wp-block-kontakt-form input[type="tel"]:focus,
.wp-block-kontakt-form textarea:focus {
	border-color: blue;
}

.wp-block-kontakt-form input[type="submit"] {
	margin-top: 20px;
	min-width: 160px;
}

.wp-block-kontakt-form p.error {
	margin-top: 10px;
	color: red;
}

.wp-block-kontakt-form p.message {
	font-weight: 500;
}

.wp-block-kontakt-form dl {
	background-color: aliceblue;
	padding: 20px;
}

.wp-block-kontakt-form dl dt {
	font-style: italic;
}
```


Or if you wanted some fancier layouts for desktop devices, add something like this as well:

```
@media (min-width: 1025px) {

	.wp-block-kontakt-form .wp-block-kontakt-form__name,
	.wp-block-kontakt-form .wp-block-kontakt-form__email,
	.wp-block-kontakt-form .wp-block-kontakt-form__telephone,
	.wp-block-kontakt-form .wp-block-kontakt-form__company {
		width: 50%;
		display: inline-block;
	}

	.wp-block-kontakt-form .wp-block-kontakt-form__message {
		width: 100%;
		display: inline-block;
	}

	.wp-block-kontakt-form .wp-block-kontakt-form__name,
	.wp-block-kontakt-form .wp-block-kontakt-form__telephone {
		padding-right: 2%;
		float: left;
	}

	.wp-block-kontakt-form .wp-block-kontakt-form__email,
	.wp-block-kontakt-form .wp-block-kontakt-form__company {
		padding-left: 2%;
		float: right;
	}

}
```

Kontakt does't store the captured messages by default but provides an API to allow you to do so if you wish. See this example:

```
// ---- Store messages in Wordpress database.
add_filter('kontakt_store_messages', 'custom_kontakt_store_messages_filter', 10, 1);
function custom_kontakt_store_messages_filter($store) {
	return true;
}
```

This would enable captured messages to be stored to the posts table as a custom post type "kontakt" and make them available for viewing, filtering, sorting, export and deletion from the Tools menu in the admin dashboard.
