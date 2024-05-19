const closeServiceModalBtn = document.getElementById("closeServiceModalBtn");
const openServiceModalBtn = document.querySelectorAll(".openServiceModalBtn");

if(closeServiceModalBtn){
    closeServiceModalBtn.addEventListener("click", ()=>{
        const ServiceModal = document.getElementById("ServiceModal")
        ServiceModal.classList.add("d-none")
    })
}

if(openServiceModalBtn){
    openServiceModalBtn.forEach(element => {
        element.addEventListener("click", ()=>{
            const ServiceModal = document.getElementById("ServiceModal")
            ServiceModal.classList.remove("d-none")
        })
    });
}

const gallery = document.querySelector('.gallery');
const modal = document.querySelector('.modal');
const modalContent = document.querySelector('.img-modal-content');
const closeBtn = document.querySelector('.close');

gallery.addEventListener('click', event => {
  if (event.target.tagName === 'IMG') {
    const imgSrc = event.target.getAttribute('src');
    modalContent.innerHTML = `<img src="${imgSrc}">`;
    modal.style.display = 'block';
  }
});

closeBtn.addEventListener('click', () => {
  modal.style.display = 'none';
});