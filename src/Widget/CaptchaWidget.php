<?php

declare(strict_types=1);

namespace PBDKN\ContaoCaptchaBundle\Widget;

use Contao\BackendTemplate;
use Contao\Config;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\System;
use Contao\Widget;

use PBDKN\ContaoCaptchaBundle\Service\CaptchaService;

class CaptchaWidget extends Widget
{
    protected $strTemplate = 'form_tossn_captcha';
    protected $strPrefix = 'widget widget-text';

    private CaptchaService $captchaService;
    private Config $config;

    public function __construct(array|null $arrAttributes = null)
    {
        parent::__construct($arrAttributes);

        $this->config = Config::getInstance();
        $this->captchaService = System::getContainer()->get(CaptchaService::class);
    }

    public function parse($attributes = null): string
    {
        if (!$this->config->get('tc_captchaimage')) {
            return '';
        }

        return $this->generate();
    }

    public function generate(): string
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            $template = new BackendTemplate('be_wildcard');
            $template->wildcard = '### TOSSN CAPTCHA ###';
            $template->title = $this->name ?: 'TOSSN Captcha';
            $template->id = $this->id;
            $template->link = $this->name;
            $template->href = 'contao?do=form&table=tl_form_field&id=' . $this->id;

            return $template->parse();
        }

        $this->captchaService->createCaptcha();

        $GLOBALS['TL_CSS']['tossn_captcha'] = 'bundles/contaocaptcha/css/tossn-captcha.css|static';

    // Fehlerbehandlung
        $hasErrors = $this->hasErrors();

        $template = new FrontendTemplate('form_tossn_captcha');
        $template->id = $this->id;
        $template->name = $this->strName;
        $template->label = $this->label;
        $template->class = trim('widget captcha-widget ' . $this->strClass);
        $template->value = '';
        $template->mandatory = $this->mandatory;
        $template->attributes = $this->getAttributes();
        $template->captcha_hash = $this->captchaService->getHash();
        $template->captcha_image = '/' . ltrim((string) $this->captchaService->getImageName(), '/');
        $template->hasErrors = $hasErrors;
        $template->error = $hasErrors ? $this->getErrorAsString() : '';
        $template->errorClass = $hasErrors ? ' has-error' : '';

        return $template->parse();
    }

    protected function validator($varInput)
    {
        $varInput = parent::validator($varInput);

        if (!$this->captchaService->checkCode(Input::post($this->strName . '_hash'), $varInput)) {
            $this->addError($GLOBALS['TL_LANG']['tossn_captcha']['error']);
        }

        return $varInput;
    }
}
