<?php

declare(strict_types=1);

namespace App\Form\Handler;

use App\Mailer\MailerService;
use App\Model\ContactSubmission;
use App\Service\RecaptchaService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactFormHandler
{
    private TranslatorInterface $translator;
	private RequestStack $requestStack;
    private MailerService $mailer;
    private RecaptchaService $recaptcha;

    public function __construct(TranslatorInterface $translator, RequestStack $requestStack, MailerService $mailer, RecaptchaService $recaptchaService)
    {
        $this->translator = $translator;
		$this->requestStack = $requestStack;
        $this->mailer = $mailer;
        $this->recaptcha = $recaptchaService;
    }

    public function handle(FormInterface $form, Request $request): bool
    {
        if (!$request->isMethod('POST')) {
            return false;
        }

        if (!$form->handleRequest($request)->isValid()) {
            return false;
        }

        /** @var ContactSubmission $data */
        $data = $form->getData();
        $captchaResult = $this->recaptcha->validateRecaptchaToken($data->getRecaptchaToken());

        // Client succeed recaptcha validation.
        if ($captchaResult['success'] !== true) {
			$this->requestStack->getSession()->getFlashBag()->add('danger', 'You seem like a robot ('.current($captchaResult['error-codes']).'), sorry.');

            return false;
        }

        $mailResult = $this->mailer->sendContactFormEmail($data);

        if (!$mailResult) {
			$this->requestStack->getSession()->getFlashBag()->add('danger', 'Mail was not sent due to unknown error.');
            return false;
        }

        $this->translator->setLocale($request->getLocale());
		$this->requestStack->getSession()->getFlashBag()->add('success', $this->translator->trans('flashes.contact.success'));

        return true;
    }
}
