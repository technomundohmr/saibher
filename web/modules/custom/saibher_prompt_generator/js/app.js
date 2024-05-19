const copyToClipboard = () => {
    promptText= document.querySelector('.prompt-text');
    if(promptText){
        let prompt = promptText.value;
        navigator.clipboard.writeText(prompt);
        let copyButton = document.querySelector('.prompt-copy-button');
        copyButton.innerHTML = 'Copiado!'
        copyButton.classList.add('copiado')
        copyButton.setAttribute('disabled', true)
        setTimeout(function() {
            copyButton.innerHTML = 'Copiar'
            copyButton.classList.remove('copiado')
            copyButton.removeAttribute('disabled')
          }, 2000);
    }
}