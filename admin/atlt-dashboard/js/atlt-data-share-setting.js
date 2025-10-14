jQuery(function ($) {
  // Constants
  const SELECTORS = {
    termsLink: ".atlt-see-terms",
    termsBox: "#termsBox",
    refreshBtn: ".atlt-refresh-btn",
    licenseContainer: ".atlt-dashboard-license-pro-container",
    deactivateBtn: ".atlt-dashboard-license-pro-container-deactivate-btn",
    validityStrong: ".validity:has(strong) strong",
    validitySpan: ".validity:not(:has(strong))",
    licenseType: ".license-type",
    containerUl: ".atlt-dashboard-license-pro-container ul",
    errorNotice: ".atlt-dashboard-license-pro-container div.notice.notice-error",
  };

  const CSS_CLASSES = {
    valid: "valid",
    invalid: "invalid",
    expired: "expired",
    supportExpired: "support-expired",
    noSupport: "no-support"
  };

  const MESSAGES = {
    validLicense: "✅ Valid",
    invalidLicense: "❌ Invalid", 
    expiredLicense: "❌ License Expired",
    expiredSupport: "❌ Support Expired",
    genericError: "An error occurred while refreshing the license. Please try again.",
    checkingStatus: "⏳Checking....."
  };

  const URLS = {
    renewLink: "https://my.coolplugins.net/account/subscriptions/"
  };

  // Helper Functions
  const createNoticeElement = (type, message, isError = false) => {
    const noticeClass = isError ? "notice-error" : "notice-success";
    return $(`<div class="notice ${noticeClass} is-dismissible"><p>${message}</p></div>`);
  };

  const createRenewalNotice = (message) => {
    return $(`<div style="margin-top: 10px; color: #d63638;" class="notice notice-error">${message}</div>`);
  };

  const isDateExpired = (dateString) => {
    if (!dateString || dateString.toLowerCase() === "no expiry" || dateString.toLowerCase() === "unlimited") {
      return false;
    }
    return new Date(dateString).getTime() < Date.now();
  };

  const updateValidityStatus = ($element, status, cssClass) => {
    $element.html(status);
    $element.removeClass(Object.values(CSS_CLASSES).join(" ")).addClass(cssClass);
  };

  const getRenewalMessage = (isLicenseExpired, market, versionMessage = '') => {
    const baseMessage = isLicenseExpired 
      ? "Your license has expired, " 
      : "Your support has expired, ";
    
    const versionPrefix = versionMessage ? versionMessage + ' ' : '';
    
    if (market === "E") {
      return versionPrefix + baseMessage + "Renew now to continue receiving updates and priority support.";
    }
    
    const linkText = isLicenseExpired ? "Renew now" : 
      '<a style="color: #0073aa; text-decoration: underline;" href="' + URLS.renewLink + '" target="_blank">Renew now</a>';
    
    return isLicenseExpired 
      ? versionPrefix + baseMessage + `<a href="${URLS.renewLink}" target="_blank">Renew now</a> to continue receiving updates and priority support.`
      : versionPrefix + baseMessage + linkText + " to continue receiving updates and priority support";
  };

  const getFormattedDate = (dateString) => {
    if (dateString.toLowerCase() !== 'no expiry') {
      const date = new Date(dateString);
      return date.toLocaleDateString('en-GB', { 
        day: '2-digit', 
        month: 'short', 
        year: 'numeric' 
      });
    }
    return dateString;
  };

  const showNoticeMessage = (element, delay = 2000) => {
    element.insertBefore(SELECTORS.licenseContainer).delay(delay).fadeOut();
  };

  // Terms toggle functionality
  const $termsLink = $(SELECTORS.termsLink);
  const $termsBox = $(SELECTORS.termsBox);

  $termsLink.on("click", function (e) {
    e.preventDefault();
    const isVisible = $termsBox.toggle().is(":visible");
    $(this).html(isVisible ? "Hide Terms" : "See terms");
  });

  // License refresh functionality
  const $refreshBtn = $(SELECTORS.refreshBtn);

  $refreshBtn.on("click", function (e) {
    e.preventDefault();

    const $btn = $(this);
    const originalText = $btn.text();

    // Update button state
    $btn.prop("disabled", true).text(MESSAGES.checkingStatus);

    // AJAX request
    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "atlt_refresh_license_ajax",
        nonce: atlt_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          const successNotice = createNoticeElement("success", response.data.message);
          showNoticeMessage(successNotice);
          
          if (response.data.license_info) {
            const versionMessage = response.data.version_available_message || '';
            updateLicenseInfo(response.data.license_info, versionMessage);
          }
        } else {
          const errorNotice = createNoticeElement("error", response.data.message, true);
          showNoticeMessage(errorNotice);
          
          setTimeout(() => location.reload(), 2000);
        }
      },
      error: function () {
        const errorNotice = createNoticeElement("error", MESSAGES.genericError, true);
        showNoticeMessage(errorNotice, 5000);
      },
      complete: function () {
        $btn.prop("disabled", false).text("🔄" + originalText);
      },
    });
  });

  // License information update function
  function updateLicenseInfo(licenseInfo, versionMessage = '') {
    updateLicenseValidity(licenseInfo);
    updateLicenseType(licenseInfo);
    updateExpireDate(licenseInfo);
    handleRenewalMessages(licenseInfo, versionMessage);
    handleRefreshButtonVisibility(licenseInfo);
  }

  function updateLicenseValidity(licenseInfo) {
    const $validity = $(SELECTORS.validityStrong);

    if (!licenseInfo.is_valid) {
      updateValidityStatus($validity, MESSAGES.invalidLicense, CSS_CLASSES.invalid);
      return;
    }

    if (licenseInfo.is_valid === "license_expired") {
      updateValidityStatus($validity, MESSAGES.expiredLicense, CSS_CLASSES.expired);
    } else if (licenseInfo.support_end.toLowerCase() === "no support") {
      updateValidityStatus($validity, MESSAGES.expiredSupport, CSS_CLASSES.noSupport);
    } else if (licenseInfo.is_valid === "support_expired" && 
               isDateExpired(licenseInfo.support_end)) {
      updateValidityStatus($validity, MESSAGES.expiredSupport, CSS_CLASSES.supportExpired);
    } else {
      updateValidityStatus($validity, MESSAGES.validLicense, CSS_CLASSES.valid);
    }
  }

  function updateLicenseType(licenseInfo) {
    if (!licenseInfo.license_title) return;

    const $licenseType = $(SELECTORS.licenseType);

    if ($licenseType.length > 0) {
      $licenseType.text(licenseInfo.license_title);
    } else {
      const $ul = $(SELECTORS.containerUl);
      const $statusLi = $ul.find("li:first");
      const $newLicenseTypeLi = $(`
        <li>
          <strong>License Type:</strong> 
          <span class="license-type">${licenseInfo.license_title}</span>
        </li>
      `);
      $statusLi.after($newLicenseTypeLi);
    }
  }

  function updateExpireDate(licenseInfo) {
    if (!licenseInfo.expire_date) return;

    const $expireDateSpan = $(SELECTORS.validitySpan);
    const displayDate = getDisplayDate(licenseInfo);

    if ($expireDateSpan.length > 0) {
      $expireDateSpan.text(displayDate);
    } else {
      createExpireDateElement(displayDate);
    }
  }

  function getDisplayDate(licenseInfo) {
    const expireDateExpired = isDateExpired(licenseInfo.expire_date);
    const supportEndExpired = isDateExpired(licenseInfo.support_end);

    if (licenseInfo.support_end.toLowerCase() === "no support") {
      return "No Support";
    } else if (expireDateExpired) {
      return getFormattedDate(licenseInfo.expire_date);
    } else if (supportEndExpired) {
      return getFormattedDate(licenseInfo.support_end);
    } else {
      return getFormattedDate(licenseInfo.expire_date);
    }
  }

  function createExpireDateElement(displayDate) {
    const $ul = $(SELECTORS.containerUl);
    const $licenseTypeLi = $ul.find("li:has(.license-type)");

    if ($licenseTypeLi.length > 0) {
      const $newExpireDateLi = $(`
        <li>
          <strong>Plugin Updates & Support Validity:</strong> 
          <span class="validity">${displayDate}</span>
        </li>
      `);
      $licenseTypeLi.after($newExpireDateLi);
    }
  }
  function removeRenewalMessages() {
    $(SELECTORS.errorNotice).remove();
  }

  function handleRenewalMessages(licenseInfo, versionMessage = '') {
    const isLicenseExpired = licenseInfo.is_valid === "license_expired";
    const isSupportExpired = licenseInfo.is_valid === "support_expired" || 
                           (licenseInfo.support_end.toLowerCase() === "no support" && 
                            isDateExpired(licenseInfo.support_end));

    if (isLicenseExpired || isSupportExpired) {
      updateRenewalMessage(licenseInfo, isLicenseExpired, versionMessage);
    } else {
      // Remove existing error notices when license is valid
      removeRenewalMessages();
    }
  }

  function updateRenewalMessage(licenseInfo, isLicenseExpired, versionMessage = '') {
    const renewalMessage = getRenewalMessage(isLicenseExpired, licenseInfo.market, versionMessage);
    const $existingLink = $(SELECTORS.errorNotice);
    if ($existingLink.length) {
      $existingLink.html(renewalMessage);
    } else {
      $(SELECTORS.errorNotice).remove();
      const $renewalNotice = createRenewalNotice(renewalMessage);
      $(SELECTORS.deactivateBtn).after($renewalNotice);
    }
  }

  function handleRefreshButtonVisibility(licenseInfo) {
    const isValidLicense = licenseInfo.is_valid && 
                          licenseInfo.is_valid !== "license_expired" && 
                          licenseInfo.is_valid !== "support_expired" &&
                          (licenseInfo.support_end.toLowerCase() === "unlimited" || 
                           !isDateExpired(licenseInfo.support_end));

    if (isValidLicense) {
      $(SELECTORS.refreshBtn).hide();
    }
  }
});
