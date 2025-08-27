// comments.js
document.getElementById('commentForm').addEventListener('submit', function (e) {
    e.preventDefault();
  
    // Get input values
    const name = document.getElementById('name').value;
    const comment = document.getElementById('comment').value;
  
    // Create a new comment card
    const newCommentCard = document.createElement('div');
    newCommentCard.classList.add('client__card');
    newCommentCard.innerHTML = `
      <p class="commentor-name">${name}</p>
      <p class="comment-text">"${comment}"</p>
    `;
  
    // Append the new comment to the comments list
    document.getElementById('clientComments').appendChild(newCommentCard);
  
    // Clear the form fields
    document.getElementById('name').value = '';
    document.getElementById('comment').value = '';
  });
  