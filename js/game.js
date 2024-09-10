let gameBoard, flippedCards = [], playerMatches = 0, opponentMatches = 0, isYourTurn;
let lastProcessedMoveId = 0;
let pairedCards = new Set();
let isFlipping = false;

document.addEventListener('DOMContentLoaded', () => {
    gameBoard = document.getElementById('game-board');
    initGame();
});

function initGame() {
    fetch(`api/get_game_state.php?game_id=${gameId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }
            renderGameBoard(data.cards);
            updateGameInfo(data);
            processMoves(data.moves);
            startGameLoop();
        })
        .catch(error => console.error('Error:', error));
}

function renderGameBoard(cards) {
    gameBoard.innerHTML = '';
    cards.forEach((card, index) => {
        const cardElement = document.createElement('div');
        cardElement.className = 'card';
        cardElement.dataset.index = index;
        cardElement.innerHTML = `
            <div class="card-inner">
                <div class="card-front"></div>
                <div class="card-back">${card}</div>
            </div>
        `;
        cardElement.addEventListener('click', () => flipCard(cardElement, index));
        gameBoard.appendChild(cardElement);
    });
}

function flipCard(card, index) {
    if (!isYourTurn || isFlipping || flippedCards.length === 2 || card.classList.contains('flipped') || pairedCards.has(index)) return;

    isFlipping = true;
    card.classList.add('flipped');
    flippedCards.push({ element: card, index: index });

    sendFlipToServer(index)
        .then(() => {
            if (flippedCards.length === 2) {
                setTimeout(() => {
                    checkMatch();
                }, 1500);
            } else {
                isFlipping = false;
            }
        })
        .catch(error => {
            console.error('Error flipping card:', error);
            isFlipping = false;
        });
}

function sendFlipToServer(index) {
    return fetch('api/make_move.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            game_id: gameId,
            player_id: playerId,
            action: 'flip',
            card_index: index
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            throw new Error(data.error);
        }
        return data;
    });
}

function checkMatch() {
    const [card1, card2] = flippedCards;
    const isMatch = card1.element.querySelector('.card-back').textContent === 
                    card2.element.querySelector('.card-back').textContent;

    sendMatchCheckToServer(card1.index, card2.index, isMatch)
        .then(() => {
            if (isMatch) {
                playerMatches++;
                document.getElementById('your-matches').textContent = playerMatches;
                pairedCards.add(card1.index);
                pairedCards.add(card2.index);
                // Keep the turn for the current player
                isYourTurn = true;
                document.getElementById('is-your-turn').textContent = 'Yes';
            } else {
                setTimeout(() => {
                    card1.element.classList.remove('flipped');
                    card2.element.classList.remove('flipped');
                }, 1500);
                // Switch turn to the other player
                isYourTurn = false;
                document.getElementById('is-your-turn').textContent = 'No';
            }
            flippedCards = [];
            isFlipping = false;
        })
        .catch(error => {
            console.error('Error checking match:', error);
            isFlipping = false;
        });
}


function sendMatchCheckToServer(index1, index2, isMatch) {
    return fetch('api/make_move.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            game_id: gameId,
            player_id: playerId,
            action: 'check_match',
            index1: index1,
            index2: index2,
            is_match: isMatch
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            throw new Error(data.error);
        }
        return data;
    });
}

function updateGameInfo(data) {
    isYourTurn = data.current_turn === playerId;
    document.getElementById('is-your-turn').textContent = isYourTurn ? 'Yes' : 'No';
    document.getElementById('your-matches').textContent = data.your_matches;
    document.getElementById('opponent-matches').textContent = data.opponent_matches;

    const winConditionPairs = gameMode === 'visible_memory' ? 25 : 9;

    if (data.game_over || data.your_matches + data.opponent_matches === winConditionPairs) {
        let winMessage;
        if (data.your_matches > data.opponent_matches) {
            winMessage = 'You win!';
        } else if (data.your_matches < data.opponent_matches) {
            winMessage = 'You lose!';
        } else {
            winMessage = 'It\'s a tie!';
        }
        
        // Use a flag to ensure the alert is shown only once
        if (!this.gameEndAlertShown) {
            alert(winMessage);
            this.gameEndAlertShown = true;
            setTimeout(() => {
                window.location.href = 'dashboard.php';
            }, 100);
        }
    }
}
function startGameLoop() {
    setInterval(() => {
        fetch(`api/get_game_state.php?game_id=${gameId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error:', data.error);
                    return;
                }
                updateGameInfo(data);
                processMoves(data.moves);
            })
            .catch(error => console.error('Error:', error));
    }, 1000);
}

function processMoves(moves) {
    const unflipCards = new Set();

    moves.forEach(move => {
        if (move.id > lastProcessedMoveId) {
            const cardElement = document.querySelector(`.card[data-index="${move.card_index}"]`);
            if (cardElement) {
                if (move.action === 'flip') {
                    cardElement.classList.add('flipped');
                } else if (move.action === 'unflip') {
                    unflipCards.add(cardElement);
                } else if (move.action === 'match') {
                    cardElement.classList.add('flipped', 'matched');
                    pairedCards.add(parseInt(move.card_index));
                }
            }
            lastProcessedMoveId = move.id;
        }
    });

    unflipCards.forEach(card => {
        card.classList.remove('flipped');
    });
}

initGame();
