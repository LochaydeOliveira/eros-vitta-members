// Função para carregar o arquivo JSON e preencher os cards
fetch('assets-agencia-led/conteudo-cards.json')
  .then(response => response.json())
  .then(data => {
    const cardContainer = document.getElementById('card-container');
    
    data.cards.forEach(card => {
      const cardElement = document.createElement('div');
      cardElement.classList.add('col');
      
      cardElement.innerHTML = `
        <div class="border-0 card h-100 p-lg-4 rounded-5 shadow-lg">
          <div class="card-body card-body-pers">
            <h5 class="card-title fw-bold h5-agl-plans-tt">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-shop-window" viewBox="0 0 16 16">
                <path d="..."/>
              </svg>
              ${card.title}
            </h5>
            <img class="img-cards-vnd" width="300" src="assets-agencia-led/img/img-loja-01.png" alt="Imagem de Vendas Loja 1">
            <div class="mt-3">
              <p class="mb-1 price-main"><span>R$</span>${card.price}</p>
            </div>
            <span class="sub-tt-plans">${card.subTitle}</span>
            <span class="sub-tt-plans-expl">${card.description}</span>
            <p class="mb-1 mg-left-gd"><strong>Recursos em destaque</strong></p>
            <ul class="list-group list-group-flush p-tp-dw">
              ${card.features.map(feature => `
                <li class="list-group-item d-flex align-items-center font-sz-itns-pls">
                  <span class="me-2">
                    <img src="assets-agencia-led/icones-svg/check.svg" alt="Icone SVG">
                  </span>
                  ${feature}
                </li>`).join('')}
            </ul>
            <div class="area-pay">
              <a href="${card.buttonLink}" class="btn-pay btn btn-section-primary btn-lg" target="_blank">${card.buttonText}</a>
              <p class="card-text des-plans">
                ${card.paymentText}
              </p>
              <p class="card-text text-contions">
                ${card.paymentConditions} <img style="margin: 0 2px 0 0" src="assets-agencia-led/icones-svg/icons8-foto-vazado.svg" alt="icone pix"><img src="assets-agencia-led/icones-svg/cartao-de-credito.svg" alt="pagamento crédito">
              </p>
            </div>
          </div>
        </div>
      `;
      cardContainer.appendChild(cardElement);
    });
  })
  .catch(error => console.error('Erro ao carregar o arquivo JSON:', error));




function carregarElemento(id, arquivo) {
  fetch(arquivo)
    .then(response => response.text())
    .then(data => {
      document.getElementById(id).innerHTML = data;
    })
    .catch(error => console.error('Erro ao carregar o arquivo:', error));
}

carregarElemento('header', 'header.html');
carregarElemento('footer', 'footer.html');

