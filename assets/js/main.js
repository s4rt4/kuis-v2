let quizData = [];
let currentQuestion = 0;
let score = 0;

const questionEl = document.getElementById("question");
const optionsEl = document.getElementById("options");
const nextBtn = document.getElementById("nextBtn");
const quizContainer = document.getElementById("quiz-container");
const categoryBtns = document.querySelectorAll(".category-btn");
const scoreContainer = document.getElementById("score-container");
const scoreText = document.getElementById("score-text");
const correctSound = document.getElementById("correctSound");
const wrongSound = document.getElementById("wrongSound");

// Ambil soal via fetch ke PHP lokal
categoryBtns.forEach(btn => {
    btn.addEventListener("click", () => {
        const categoryId = btn.getAttribute("data-id");
        fetch(`fetch_questions.php?category=${categoryId}`)
        .then(res => res.json())
        .then(data => {
            quizData = data;
            currentQuestion = 0;
            score = 0;
            document.getElementById("category-select").classList.add("d-none");
            quizContainer.classList.remove("d-none");
            loadQuestion();
        });
    });
});

function loadQuestion() {
    nextBtn.classList.add("d-none");
    const q = quizData[currentQuestion];
    questionEl.innerHTML = q.question_text;
    optionsEl.innerHTML = "";
    ["A","B","C","D"].forEach(opt => {
        const btn = document.createElement("button");
        btn.className = "btn btn-light m-2 option-btn";
        btn.innerHTML = q["option_" + opt.toLowerCase()];
        btn.addEventListener("click", () => selectOption(opt));
        optionsEl.appendChild(btn);
    });
    MathJax.typeset();
    gsap.from(questionEl, {duration:0.5, y:-20, opacity:0});
}

function selectOption(selected) {
    const correct = quizData[currentQuestion].correct_option;
    if(selected === correct){
        score++;
        correctSound.play();
    } else {
        wrongSound.play();
    }
    currentQuestion++;
    if(currentQuestion < quizData.length){
        nextBtn.classList.remove("d-none");
        nextBtn.onclick = loadQuestion;
    } else {
        showScore();
    }
}

function showScore() {
    quizContainer.classList.add("d-none");
    scoreContainer.classList.remove("d-none");
    scoreText.textContent = `Kamu mendapat ${score} dari ${quizData.length} soal! 🎉`;
    gsap.from(scoreText, {duration:1, scale:0, rotation:360});
}
