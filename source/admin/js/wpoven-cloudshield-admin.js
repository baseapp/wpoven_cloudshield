(function ($) {
  "use strict";

  /**
   * All of the code for your admin-facing JavaScript source
   * should reside in this file.
   *
   * Note: It has been assumed you will write jQuery code here, so the
   * $ function reference has been prepared for usage within the scope
   * of this function.
   *
   * This enables you to define handlers, for when the DOM is ready:
   *
   * $(function() {
   *
   * });
   *
   * When the window is loaded:
   *
   * $( window ).load(function() {
   *
   * });
   *
   * ...and/or other possibilities.
   *
   * Ideally, it is not considered best practise to attach more than a
   * single DOM-ready or window-load handler for a particular page.
   * Although scripts in the WordPress core, Plugins and Themes may be
   * practising this, we should strive to set a better example in our own work.
   */

  function showModal(title, message, onOk) {
    // Create the modal elements
    const modalOverlay = document.createElement("div");
    modalOverlay.className = "modal-overlay";

    const modal = document.createElement("div");
    modal.className = "modal";

    const modalContent = document.createElement("div");
    modalContent.className = "modal-content";

    const modalHeader = document.createElement("div");
    modalHeader.className = "modal-header";
    modalHeader.innerHTML = `<h2 style="color: green;">${title}</h2>`;

    const modalBody = document.createElement("div");
    modalBody.className = "modal-body";
    modalBody.innerHTML = `<p>${message}</p>`;

    const modalFooter = document.createElement("div");
    modalFooter.className = "modal-footer";

    const okButton = document.createElement("button");
    okButton.className = "ok";
    okButton.innerText = "OK";
    okButton.style.backgroundColor = "#0073aa";
    okButton.style.border = "none"; // Remove border
    okButton.style.color = "white"; // Optional: Set text color for better contrast
    okButton.style.padding = "10px 20px"; // Optional: Add padding for better appearance
    okButton.style.cursor = "pointer"; // Optional: Change cursor on hover
    okButton.style.borderRadius = "5px";
    okButton.onclick = function () {
      document.body.removeChild(modalOverlay); // Remove the modal overlay
      document.body.removeChild(modal); // Remove the modal
      if (onOk) onOk(); // Call the onOk callback
    };

    modalFooter.appendChild(okButton);
    modalContent.appendChild(modalHeader);
    modalContent.appendChild(modalBody);
    modalContent.appendChild(modalFooter);
    modal.appendChild(modalContent);

    document.body.appendChild(modalOverlay);
    document.body.appendChild(modal);

    // Optional: Style the modal overlay (semi-transparent background)
    modalOverlay.style.position = "fixed";
    modalOverlay.style.top = "0";
    modalOverlay.style.left = "0";
    modalOverlay.style.width = "100%";
    modalOverlay.style.height = "100%";
    modalOverlay.style.backgroundColor = "rgba(0, 0, 0, 0.5)";
    modalOverlay.style.zIndex = "999"; // Ensure it is above other elements

    // Optional: Style the modal
    modal.style.position = "fixed";
    modal.style.zIndex = "1000"; // Ensure the modal is above the overlay
    modal.style.display = "flex";
    modal.style.alignItems = "center";
    modal.style.justifyContent = "center";
    modal.style.top = "0";
    modal.style.left = "0";
    modal.style.width = "100%";
    modal.style.height = "100%";

    // Style the modal content
    modalContent.style.backgroundColor = "white";
    modalContent.style.padding = "20px";
    modalContent.style.borderRadius = "5px";
    modalContent.style.boxShadow = "0 2px 10px rgba(0, 0, 0, 0.1)";
    modalContent.style.width = "400px";
    modalContent.style.maxWidth = "90%";
  }

  async function cloudshieldEnablePhpWafRules() {
    const ajax_nonce = document.getElementById("wpoven-ajax-nonce").innerText;
    const ajax_url = document.getElementById("wpoven-ajax-url").innerText;

    const response = await fetch(ajax_url, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=utf-8",
      },
      body: `action=cloudshield_enable_php_waf_rules&security=${ajax_nonce}`,
      credentials: "same-origin",
      timeout: 10000,
    });

    if (response.ok) {
      const data = await response.json();
      if (data.status === "ok") {
        showModal(
          "Cloudshield PHP WAF Rules!",
          "All PHP WAF rules enabled successfully.",
          function () {
            // Refresh the page when OK is clicked
            location.reload();
          }
        );
      } else {
        showModal(
          "Cloudshield PHP WAF Rules!",
          "Please save the cloudshield settings.",
          function () {
            location.reload();
          }
        );
      }
    }
  }

  async function cloudshieldDisablePhpWafRules() {
    const ajax_nonce = document.getElementById("wpoven-ajax-nonce").innerText;
    const ajax_url = document.getElementById("wpoven-ajax-url").innerText;

    const response = await fetch(ajax_url, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=utf-8",
      },
      body: `action=cloudshield_disable_php_waf_rules&security=${ajax_nonce}`,
      credentials: "same-origin",
      timeout: 10000,
    });

    if (response.ok) {
      const data = await response.json();
      if (data.status === "ok") {
        showModal(
          "Cloudshield PHP WAF Rules!",
          "All PHP WAF rules disable successfully.",
          function () {
            // Refresh the page when OK is clicked
            location.reload();
          }
        );
      }
    } else {
      showModal("Cloudshield PHP WAF Rules!", "Something wrong.", function () {
        location.reload();
      });
    }
  }

  async function cloudShieldPurgeAllLogs() {
    const ajax_nonce = document.getElementById("wpoven-ajax-nonce").innerText;
    const ajax_url = document.getElementById("wpoven-ajax-url").innerText;

    const response = await fetch(ajax_url, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=utf-8",
      },
      body: `action=cloudshield_purge_all_logs&security=${ajax_nonce}`,
      credentials: "same-origin",
      timeout: 10000,
    });

    if (response.ok) {
      const data = await response.json();
      if (data.status === "ok") {
        showModal(
          "Purge All CloudShield Logs!",
          "All log data purge successfully.",
          function () {
            // Refresh the page when OK is clicked
            location.reload();
          }
        );
      }
    } else {
      showModal("Cloudflare WAF Rules!", "Something wrong.", function () {
        location.reload();
      });
    }
  }

  async function cloudShieldCreateWAFRules() {
    const ajax_nonce = document.getElementById("wpoven-ajax-nonce").innerText;
    const ajax_url = document.getElementById("wpoven-ajax-url").innerText;

    const response = await fetch(ajax_url, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=utf-8",
      },
      body: `action=cloudshield_create_waf_custom_rule&security=${ajax_nonce}`,
      credentials: "same-origin",
      timeout: 10000,
    });

    if (response.ok) {
      const data = await response.json();
      console.log(data);
      // Construct the message dynamically
      let message = "";
      if (data) {
        if (data.status.login_captcha === "ok") {
          message += data.success_msg.login_captcha;
        } else {
          message += data.error_msg.login_captcha;
        }

        message += "<br>"; // Adding a newline separator between the two messages

        if (data.status.ip_block === "ok") {
          message += data.success_msg.ip_block;
        } else {
          message += data.error_msg.ip_block;
        }

        // Show the modal with the constructed message
        showModal("Cloudflare WAF Rules!", message, function () {
          location.reload();
        });
      } else {
        showModal(
          "Cloudflare WAF Rules!",
          "Cloudflare API are not working.Please check Email/API key or try after some time.",
          function () {
            location.reload();
          }
        );
      }
    } else {
      showModal(
        "Cloudflare WAF Rules!",
        "Cloudflare API are not working.Please check Email/API key or try after some time.",
        function () {
          location.reload();
        }
      );
    }
  }

  async function resetAllsetitngsAndWAFRules() {
    const ajax_nonce = document.getElementById("wpoven-ajax-nonce").innerText;
    const ajax_url = document.getElementById("wpoven-ajax-url").innerText;

    const response = await fetch(ajax_url, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=utf-8",
      },
      body: `action=cloudshield_reset_all_settings_and_waf_rules&security=${ajax_nonce}`,
      credentials: "same-origin",
      timeout: 10000,
    });

    if (response.ok) {
      const data = await response.json();
      let message = "";

      if (data.status === "ok") {
        message += "All WAF rules & settings are deleted successfully.";
      } else {
        message += "WAF rules & settings are not deleted.";
      }

      showModal("Cloudflare WAF Rules!", message, function () {
        location.reload();
      });
    } else {
      showModal(
        "Cloudflare WAF Rules!",
        "Cloudflare API are not working.Please check Email/API key or try after some time.",
        function () {
          location.reload();
        }
      );
    }
  }

  async function updateWAFRules() {
    const ajax_nonce = document.getElementById("wpoven-ajax-nonce").innerText;
    const ajax_url = document.getElementById("wpoven-ajax-url").innerText;
    const response = await fetch(ajax_url, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=utf-8",
      },
      body: `action=cloudshield_update_waf_rules&security=${ajax_nonce}`,
      credentials: "same-origin",
      timeout: 10000,
    });

    if (response.ok) {
      const data = await response.json();
      let message = "";

      if (data.status === "ok") {
        cloudShieldCreateWAFRules();
        message += "All WAF rules are updated successfully.";
      } else {
        message += "WAF rules are not updated. No custom WAF rules fined.";
      }
      showModal("Cloudflare WAF Rules!", message);
    } else {
      showModal(
        "Cloudflare WAF Rules!",
        "Cloudflare API are not working.Please check Email/API key or try after some time.",
        function () {
          location.reload();
        }
      );
    }
  }

  async function disableWAFRules() {
    const ajax_nonce = document.getElementById("wpoven-ajax-nonce").innerText;
    const ajax_url = document.getElementById("wpoven-ajax-url").innerText;

    const response = await fetch(ajax_url, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=utf-8",
      },
      body: `action=cloudshield_disable_waf_rules&security=${ajax_nonce}`,
      credentials: "same-origin",
      timeout: 10000,
    });

    if (response.ok) {
      const data = await response.json();
      let message = "";

      if (data.status === "ok") {
        message += "All WAF rules are disabled successfully.";
      } else {
        message += "WAF rules are not disabled. No custom WAF rules fined.";
      }
      showModal("Cloudflare WAF Rules!", message, function () {
        location.reload();
      });
    } else {
      showModal(
        "Cloudflare WAF Rules!",
        "Cloudflare API are not working.Please check Email/API key or try after some time.",
        function () {
          location.reload();
        }
      );
    }
  }

  async function cloudShieldCreatePHPWAFRules() {
    const ajax_nonce = document.getElementById("wpoven-ajax-nonce").innerText;
    const ajax_url = document.getElementById("wpoven-ajax-url").innerText;

    const response = await fetch(ajax_url, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=utf-8",
      },
      body: `action=cloudshield_enable_php_waf_rules&security=${ajax_nonce}`,
      credentials: "same-origin",
      timeout: 10000,
    });

    if (response.ok) {
      const data = await response.json();
      let message = "";

      if (data.status === "ok") {
        message += "PHP WAF rules are enable successfully.";
      } else {
        message += "PHP WAF rules are not enable.";
      }
      showModal("PHP WAF Rules!", message, function () {
        location.reload();
      });
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    //remove extra menu title
    const menuItems = document.querySelectorAll("li#toplevel_page_wpoven");
    const menuArray = Array.from(menuItems);
    for (let i = 1; i < menuArray.length; i++) {
      menuArray[i].remove();
    }

    // Remove the top-level menu item for the plugin.
    var element = document.querySelector("li.toplevel_page_wpoven-cloudshield");
    if (element) {
      element.remove();
    }

    var $divide = document.querySelector("div#divide-divide");
    if ($divide) {
      $divide.style.display = "none";
    }

    var $divide1 = document.querySelector("div#divide-divide-1");
    if ($divide1) {
      $divide1.style.display = "none";
    }

    var $divide2 = document.querySelector("div#divide-divide-2");
    if ($divide2) {
      $divide2.style.display = "none";
    }

    var liElement = document.querySelector("li.cloudshield-logs");
    if (liElement) {
      //liElement.classList.remove("redux-group-tab-link-li");
      var firstAElement = liElement.querySelector("a");
      if (firstAElement) {
        firstAElement.remove();
      }
    }

    var liElement = document.querySelector("li.cloudshield-ip-block-logs");
    if (liElement) {
      //liElement.classList.remove("redux-group-tab-link-li");
      var firstAElement = liElement.querySelector("a");
      if (firstAElement) {
        firstAElement.remove();
      }
    }

    var purgeAllLogs = document.querySelector(
      '[id="cloudshield_submit_purge_all_logs"]'
    );

    if (purgeAllLogs) {
      purgeAllLogs.addEventListener("click", function (event) {
        event.preventDefault(); // Prevent any default action
        event.stopPropagation(); // Stop the event from bubbling up
        if (confirm("Are you want to Purge All Logs?")) {
          cloudShieldPurgeAllLogs();
        }
      });
    }

    var enableWafSettings = document.querySelector(
      '[id="cloudshield_submit_enable_waf_settings"]'
    );

    if (enableWafSettings) {
      enableWafSettings.addEventListener("click", function (event) {
        event.preventDefault(); // Prevent any default action
        event.stopPropagation(); // Stop the event from bubbling up
        if (confirm("Are you want to enable WAF custom rules?")) {
          cloudShieldCreateWAFRules();
        }
      });
    }

    var enablePHPWafSettings = document.querySelector(
      '[id="cloudshield_submit_enable_waf_settings"]'
    );

    if (enablePHPWafSettings) {
      enablePHPWafSettings.addEventListener("click", function (event) {
        event.preventDefault(); // Prevent any default action
        event.stopPropagation(); // Stop the event from bubbling up
        if (confirm("Are you want to enable PHP WAF rules?")) {
          cloudShieldCreatePHPWAFRules();
        }
      });
    }

    var resetAll = document.querySelector(
      '[id="cloudshield_submit_reset_all"]'
    );

    if (resetAll) {
      resetAll.addEventListener("click", function (event) {
        event.preventDefault(); // Prevent any default action
        event.stopPropagation(); // Stop the event from bubbling up
        if (
          confirm(
            "Are you want reset all settings and delete all WAF custom Rules?"
          )
        ) {
          resetAllsetitngsAndWAFRules();
        }
      });
    }

    var updateRules = document.querySelector(
      '[id="cloudshield_submit_update_waf_settings"]'
    );

    if (updateRules) {
      updateRules.addEventListener("click", function (event) {
        event.preventDefault(); // Prevent any default action
        event.stopPropagation(); // Stop the event from bubbling up
        if (confirm("Are you want to update all WAF custom Rules?")) {
          updateWAFRules();
        }
      });
    }

    var disableRules = document.querySelector(
      '[id="cloudshield_submit_disable_waf_settings"]'
    );

    if (disableRules) {
      disableRules.addEventListener("click", function (event) {
        event.preventDefault(); // Prevent any default action
        event.stopPropagation(); // Stop the event from bubbling up
        if (confirm("Are you want to disable all WAF custom Rules?")) {
          disableWAFRules();
        }
      });
    }

    var enablePhpWafRules = document.querySelector(
      '[id="cloudshield_submit_enable_php_waf_rules"]'
    );

    if (enablePhpWafRules) {
      enablePhpWafRules.addEventListener("click", function (event) {
        event.preventDefault(); // Prevent any default action
        event.stopPropagation(); // Stop the event from bubbling up
        if (confirm("Are you want to enable PHP WAF Rules?")) {
          cloudshieldEnablePhpWafRules();
        }
      });
    }

    var disablePhpWafRules = document.querySelector(
      '[id="cloudshield_submit_disable_php_waf_settings"]'
    );

    if (disablePhpWafRules) {
      disablePhpWafRules.addEventListener("click", function (event) {
        event.preventDefault(); // Prevent any default action
        event.stopPropagation(); // Stop the event from bubbling up
        if (confirm("Are you want to disable PHP WAF Rules?")) {
          cloudshieldDisablePhpWafRules();
        }
      });
    }
  });

  $(function () {
    // Function to validate email using regex
    function isValidEmail(email) {
      var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailRegex.test(email);
    }

    $('input[name="redux_save"]')
      .parent()
      .click(function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();

        // Create a form element
        const form = document.createElement("form");
        form.setAttribute("method", "post");

        // Array of configuration objects
        const ids = [
          {
            context: "id",
            field: "select",
            type: "text",
            id: "cloudshield-cf-auth-mode-select",
          },
          {
            context: "id",
            field: "input",
            type: "email",
            id: "cloudshield-cf-email",
          },
          {
            context: "id",
            field: "input",
            type: "password",
            id: "cloudshield-cf-apikey",
          },
          {
            context: "id",
            field: "select",
            type: "text",
            id: "cloudshield-cf-zoneid-select",
          },
          {
            context: "id",
            field: "input",
            type: "password",
            id: "cloudshield-cf-apitoken",
          },
          // {
          //   context: "id",
          //   field: "input",
          //   type: "text",
          //   id: "cloudshield-cf-apitoken-domain",
          // },
          {
            context: "id",
            field: "input",
            type: "text",
            id: "cloudshield-cf-enable-captcha",
          },
          {
            context: "id",
            field: "input",
            type: "text",
            id: "cloudshield-cf-block-xmlrpc",
          },
          {
            context: "id",
            field: "input",
            type: "text",
            id: "cloudshield-cf-wrong-login",
          },
          {
            context: "id",
            field: "input",
            type: "number",
            id: "cloudshield-login-block-request-rate",
          },
          {
            context: "id",
            field: "input",
            type: "text",
            id: "cloudshield-cf-country-block",
          },
          {
            context: "id",
            field: "select",
            type: "text",
            id: "cloudshield-country-list-select",
          },
          {
            context: "id",
            field: "input",
            type: "text",
            id: "cloudshield-cf-request-rate",
          },
          {
            context: "id",
            field: "input",
            type: "number",
            id: "cloudshield-request-rate",
          },
          {
            context: "id",
            field: "input",
            type: "text",
            id: "cloudshield-cf-ip-block",
          },
          {
            context: "id",
            field: "select",
            type: "text",
            id: "cloudshield-ip-list-select",
          },
          {
            context: "id",
            field: "input",
            type: "text",
            id: "cloudshield-cf-block-non-seo",
          },
          {
            context: "id",
            field: "input",
            type: "text",
            id: "cloudshield-cf-block-ai-crawlers",
          },
          {
            context: "id",
            field: "input",
            type: "text",
            id: "cloudshield-cf-404-protection",
          },
        ];

        // Loop through the array and append inputs to the form
        ids.forEach(({ context, field, type, id }) => {
          const selector = `${field}[${context}="${id}"]`;
          const element = document.querySelector(selector);

          if (element) {
            //console.log(element.value);
            const value =
              field === "select"
                ? Array.from(element.selectedOptions)
                    .map((option) => option.value)
                    .join(",")
                : element.value || "";

            const newInput = document.createElement("input");
            newInput.type = type;
            newInput.name = id;
            newInput.value = value;

            form.appendChild(newInput);
          }
        });

        var wafMethod = document.querySelector(
          '[id="cloudshield-waf-method-select"]'
        );
        if (wafMethod.value === "1") {
          // Check if the email field is valid before submitting the form
          var emailField = document.querySelector(
            '[id="cloudshield-cf-email"]'
          );
          if (emailField && !isValidEmail(emailField.value)) {
            alert("Please enter a valid email address.");
            return false; // Prevent form submission
          }

          var mode = document.querySelector(
            '[id="cloudshield-cf-auth-mode-select"]'
          );

          if (mode && mode.value === "0") {
            var apiKey = document.querySelector('[id="cloudshield-cf-apikey"]');
            if (apiKey.value.trim() === "") {
              alert("Please fill all required fields.");
              return false;
            }
          } else {
            var apiToken = document.querySelector(
              '[id="cloudshield-cf-apitoken"]'
            );
            // var apiTokenDomain = document.querySelector(
            //   '[id="cloudshield-cf-apitoken-domain"]'
            // );
            if (apiToken.value.trim() === "") {
              alert("Please fill all required fields.");
              return false;
            }
          }
        }

        document.body.appendChild(form);
        setTimeout(function () {
          form.submit();
          $("#1_section_group_li_a").click();
        }, 1000);
      });
  });
})(jQuery);
