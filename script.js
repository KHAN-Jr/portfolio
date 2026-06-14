const statusMessage =
document.getElementById("status-message");
emailjs.init({
    publicKey: "o5KssM6CBtauGpN3K",
    blockHeadless: false
});

document
.getElementById("contact-form")
.addEventListener("submit", function(e){

    e.preventDefault();
     const company =
       document.querySelector('[name="company"]');

      if(company.value !== ""){
         return;
     }
    const btn =
    this.querySelector("button");
    btn.disabled = true;
    btn.innerText = "Sending...";
    emailjs.sendForm(
        "service_jngw8ge",
        "template_hwtl8le",
        this
    )

    .then(() => {
        statusMessage.innerHTML =
"✅ Thank you! Your message has been sent.";
        this.reset();
        btn.disabled = false;
        btn.innerText = "Send Message";
    })
    .catch((error) => {
        console.log("FULL ERROR:", error);
    alert(
        "Status: " + error.status +
        "\nText: " + error.text
        );
        btn.disabled = false;
        btn.innerText = "Send Message";
    });
});
// active navigation....
const sections =
document.querySelectorAll("section");

const navLinks =
document.querySelectorAll(".nav-links a");

window.addEventListener("scroll", ()=>{

let current="";

sections.forEach(section=>{

const top=
section.offsetTop;

const height=
section.clientHeight;

if(pageYOffset >= top - 200){

current = section.getAttribute("id");

}

});

navLinks.forEach(link=>{

link.classList.remove("active");

if(
link.getAttribute("href")
=== "#" + current
){
link.classList.add("active");
}

});

});
//scroll reveal animation....

ScrollReveal().reveal(
'.service-card',
{
delay:200,
distance:'50px',
origin:'bottom',
interval:100
}
);

ScrollReveal().reveal(
'.project-card',
{
delay:300,
distance:'50px',
origin:'bottom',
interval:100
}
);
// Loading screen...

window.addEventListener("load",()=>{

document
.getElementById("loader")
.style.display="none";

});
// Gallery.....

const galleryImages =
document.querySelectorAll(
'.gallery-container img'
);

const lightbox =
document.getElementById(
'lightbox'
);

const lightboxImg =
document.getElementById(
'lightbox-img'
);

const closeBtn =
document.getElementById(
'close-btn'
);

galleryImages.forEach(img=>{
img.addEventListener("click",()=>{
lightbox.style.display="flex";
lightboxImg.src = img.src;
});
});

closeBtn.addEventListener("click",()=>{
lightbox.style.display="none";

});

lightbox.addEventListener("click",(e)=>{
if(e.target===lightbox){
lightbox.style.display="none";
}
});
