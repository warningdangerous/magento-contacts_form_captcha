<?php
/**
 * Contacts Form Captcha index controller
 *
 * @category    OlegKoval
 * @package     OlegKoval_ContactsFormCaptcha
 * @copyright   Copyright (c) 2012 Oleg Koval
 * @author      Oleg Koval <oleh.koval@gmail.com>
 */
//include controller to override it
require_once(Mage::getBaseDir('app') . DS .'code'. DS .'core'. DS .'Mage'. DS .'Contacts'. DS .'controllers'. DS .'IndexController.php');

class OlegKoval_ContactsFormCaptcha_IndexController extends Mage_Contacts_IndexController {
    const XML_PATH_CFC_ENABLED     = 'contacts/olegkoval_contactsformcaptcha/enabled';
    const XML_PATH_CFC_PUBLIC_KEY  = 'contacts/olegkoval_contactsformcaptcha/public_key';
    const XML_PATH_CFC_PRIVATE_KEY = 'contacts/olegkoval_contactsformcaptcha/private_key';
    const XML_PATH_CFC_THEME       = 'contacts/olegkoval_contactsformcaptcha/theme';
    const XML_PATH_CFC_LANG        = 'contacts/olegkoval_contactsformcaptcha/lang';

    /**
     * Check if "Contacts Form Captcha" is enabled
     */
    public function preDispatch() {
        parent::preDispatch();
    }

    /**
     * Method which handle action of displaying contact form
     */
    public function indexAction() {
        $this->loadLayout();

        if (Mage::getStoreConfigFlag(self::XML_PATH_CFC_ENABLED)) {
            //include reCaptcha library
            require_once(Mage::getBaseDir('lib') . DS .'reCaptcha'. DS .'recaptchalib.php');
            
            //create captcha html-code
            $publickey = Mage::getStoreConfig(self::XML_PATH_CFC_PUBLIC_KEY);
            $captcha_code = recaptcha_get_html($publickey);

            //get reCaptcha theme name
            $theme = Mage::getStoreConfig(self::XML_PATH_CFC_THEME);
            if (strlen($theme) == 0 || !in_array($theme, array('red', 'white', 'blackglass', 'clean'))) {
                $theme = 'red';
            }

            //get reCaptcha lang name
            $lang = Mage::getStoreConfig(self::XML_PATH_CFC_LANG);
            if (strlen($lang) == 0 || !in_array($lang, array('en', 'nl', 'fr', 'de', 'pt', 'ru', 'es', 'tr'))) {
                $lang = 'en';
            }
            //small hack for language feature - because it's not working as described in documentation
            $captcha_code = str_replace('?k=', '?hl='. $lang .'&amp;k=', $captcha_code);

            $this->getLayout()->getBlock('contactForm')->setTemplate('contactsformcaptcha/form.phtml')
                                                        ->setFormAction(Mage::getUrl('*/*/post'))
                                                        ->setCaptchaCode($captcha_code)
                                                        ->setCaptchaTheme($theme)
                                                        ->setCaptchaLang($lang);
        }
        else {
            $this->getLayout()->getBlock('contactForm')->setFormAction(Mage::getUrl('*/*/post'));
        }

        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');
        $this->renderLayout();
    }

    /**
     * Handle post request of Contact form
     * @return [type] [description]
     */
    public function postAction() {
        if (Mage::getStoreConfigFlag(self::XML_PATH_CFC_ENABLED)) {
            try {
                $locale = Mage::app()->getLocale()->getLocale();
				$post = $this->getRequest()->getPost();
				
                if ($post) {
                    //include reCaptcha library
                    require_once(Mage::getBaseDir('lib') . DS .'reCaptcha'. DS .'recaptchalib.php');
                    
                    //validate captcha
                    $privatekey = Mage::getStoreConfig(self::XML_PATH_CFC_PRIVATE_KEY);
                    $remote_addr = $this->getRequest()->getServer('REMOTE_ADDR');
                    $captcha = recaptcha_check_answer($privatekey, $remote_addr, $post["recaptcha_challenge_field"], $post["recaptcha_response_field"]);

                    if (!$captcha->is_valid) {
						if ($locale == "el_GR") { throw new Exception($this->__("To κλειδί reCAPTCHA δεν είναι σωστό. Παρακαλώ δοκιμάστε ξανά."), 1);}
                        if ($locale == "en_US") {throw new Exception($this->__("The reCAPTCHA wasn't entered correctly. Go back and try it again."), 1);}
   					}
                }
                else {
                    throw new Exception('', 1);
                }
            }
            catch (Exception $e) {
                if (strlen($e->getMessage()) > 0) {
                   Mage::getSingleton('customer/session')->addError($this->__($e->getMessage()));                    
  				}
                //$this->_redirect('*/*/');
				// call func instead
				if ($locale == "en_US") {echo "<b>The reCAPTCHA wasn't entered correctly.</b>"; }
				if ($locale == "el_GR") {echo "<b>To κλειδί reCAPTCHA δεν είναι σωστό.</b>"; }
				OlegKoval_ContactsFormCaptcha_IndexController::redir($post, $locale);
                return;
            }
        }

        //everything is OK - call parent action
        parent::postAction();
    }

		public function redir($post, $locale){

		if ($locale == "en_US") {echo "<br>Error, redirecting..."; }
		if ($locale == "el_GR") {echo "<br>Σφάλμα, ανακατεύθυνση..."; }
		
		$name = $post["name"];
		$email = $post["email"];
		$telephone = $post["telephone"];
		$comment = $post["comment"];
				
	?>
	<form action="../.." name="contactForm" id="contactForm" method="post">
		<br><input name="email" id="email" title="<?php echo Mage::helper('contacts')->__('Email') ?>" value="<?php echo $email; ?>" class="input-text required-entry validate-email" type="hidden" />
		<br><input name="name" id="name" title="<?php echo Mage::helper('contacts')->__('Name') ?>" class="input-text required-entry" type="hidden"  value="<?php echo $name ; ?>"  />
		<br><input name="telephone" id="telephone" title="<?php echo Mage::helper('contacts')->__('Telephone') ?>" class="input-text" type="hidden" value="<?php echo $telephone ; ?>" />
		<br><input name="comment" id="comment" type="hidden" value="<?php echo $comment ; ?>" />
			
	</form>
	
	<SCRIPT LANGUAGE="JavaScript"><!--
setTimeout('document.contactForm.submit()',1800);
//--></SCRIPT>
	
	<?php
	}
	
}