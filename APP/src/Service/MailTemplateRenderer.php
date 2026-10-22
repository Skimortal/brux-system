<?php
// src/Service/MailTemplateRenderer.php
namespace App\Service;

use App\Enum\EmailTemplateKey;
use App\Repository\EmailTemplateRepository;
use Twig\Environment as TwigEnvironment;

class MailTemplateRenderer
{
    public function __construct(
        private readonly TwigEnvironment $twig,
        private readonly EmailTemplateRepository $repo,
        private readonly string $defaultLocale = 'de'
    ) {}

    public function render(
        EmailTemplateKey|string $key,
        ?string $locale,
        array $context
    ): RenderedMail {
        $keyValue = $key instanceof EmailTemplateKey ? $key->value : $key;

        $tpl = $this->repo->findOneByKeyLocaleFallback(
            $keyValue,
            $locale,
            $this->defaultLocale
        );

        if (!$tpl && $key instanceof EmailTemplateKey) {
            // built-in default: the template defines the blocks "subject" and "body"
            $default = $this->twig->load($key->defaultTemplate());

            return new RenderedMail(
                trim($default->renderBlock('subject', $context)),
                $default->renderBlock('body', $context),
                null,
            );
        }

        if (!$tpl) {
            throw new \RuntimeException(sprintf(
                'Mail-Template "%s" (%s) nicht gefunden.',
                $keyValue, $locale ?? '—'
            ));
        }

        $subject = $this->twig->createTemplate($tpl->getSubjectTemplate())->render($context);
        $html    = $this->twig->createTemplate($tpl->getHtmlTemplate())->render($context);
        $text    = $tpl->getTextTemplate()
            ? $this->twig->createTemplate($tpl->getTextTemplate())->render($context)
            : null;

        return new RenderedMail($subject, $html, $text);
    }
}

class RenderedMail
{
    public function __construct(
        public readonly string $subject,
        public readonly string $html,
        public readonly ?string $text
    ) {}
}
