document.getElementById("bitForm").addEventListener("submit", function(e) {
    e.preventDefault();

    let idUsuario = document.getElementById("idUsuario").value;
    let nomeUsuario = document.getElementById("nomeUsuario").value;
    let quantidadeBits = document.getElementById("quantidadeBits").value;

    if (quantidadeBits <= 0) {
        alert("A quantidade de bits deve ser maior que zero.");
        return;
    }

    let mensagem = `Usuário ${nomeUsuario} com ID ${idUsuario} comprou ${quantidadeBits} bits.`;
    document.getElementById("resultado").innerText = mensagem;
});
