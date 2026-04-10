jQuery(function ($) {
  const $searchInput = $("#product-search-input");
  const $resultsDiv = $("#product-search-results");

  let previousSearchQuery = "";
  let searchTimer = null;
  let currentRequest = null;

  function escapeHtml(text) {
    return $("<div>")
      .text(text || "")
      .html();
  }

  function getImageMarkup(imageHtml, title) {
    const tempDiv = document.createElement("div");
    tempDiv.innerHTML = imageHtml || "";

    const imgElement = tempDiv.querySelector("img");
    const src = imgElement ? imgElement.getAttribute("src") : "";

    if (!src) {
      return "";
    }

    return `<img src="${src}" width="100" height="100" alt="${escapeHtml(title)}">`;
  }

  function renderProducts(products) {
    let html = "";

    products.forEach(function (item) {
      const imageMarkup = getImageMarkup(item.image, item.title);

      html += `
        <div class="product-search-result-item">
          <a href="${item.permalink}" class="product-item">
            <div class="product-left">
              ${imageMarkup}
              <div class="product-details">
                <div class="product-title">${escapeHtml(item.title)}</div>
                ${
                  item.category
                    ? `<div class="product-category">${escapeHtml(item.category)}</div>`
                    : ""
                }
              </div>
            </div>
            <div class="product-price">${item.price || ""}</div>
          </a>
        </div>
      `;
    });

    $resultsDiv.html(html);
  }

  function renderMessage(message, className = "") {
    $resultsDiv.html(
      `<div class="product-search-message ${className}">${message}</div>`,
    );
  }

  function performSearch(searchQuery) {
    if (currentRequest) {
      currentRequest.abort();
    }

    renderMessage("Searching...", "is-loading");

    currentRequest = $.ajax({
      type: "POST",
      dataType: "json",
      url: ajaxsearch.ajaxurl,
      data: {
        action: "product_search",
        search_query: searchQuery,
      },
      success: function (response) {
        if (response && response.length > 0) {
          renderProducts(response);
        } else {
          renderMessage("No products found.", "is-empty");
        }
      },
      error: function (xhr, status) {
        if (status !== "abort") {
          renderMessage("Something went wrong. Please try again.", "is-error");
          console.error("AJAX Error:", xhr);
        }
      },
      complete: function () {
        currentRequest = null;
      },
    });
  }

  $searchInput.on("input", function () {
    const searchQuery = $(this).val().trim();

    clearTimeout(searchTimer);

    if (searchQuery === previousSearchQuery) {
      return;
    }

    searchTimer = setTimeout(function () {
      previousSearchQuery = searchQuery;

      if (!searchQuery) {
        $resultsDiv.empty();
        return;
      }

      performSearch(searchQuery);
    }, 400);
  });
});
