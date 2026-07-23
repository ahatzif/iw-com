function validateEmail(email) {
    const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

function insertAfter( newNode, referenceNode) {
    referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
}

function insertBefore( newNode, referenceNode) {
    referenceNode.parentNode.insertBefore(newNode, referenceNode);
}

function getSiblings(elem) {
    var siblings = [];
    var sibling = elem.parentNode.firstChild;
    while (sibling && sibling != elem ) {
        if (sibling.nodeType === 1 && sibling !== elem) {
            siblings.push(sibling);
        }
        sibling = sibling.nextSibling
    }
    return siblings;
}



export {
    validateEmail,
    insertAfter,
    insertBefore,
    getSiblings
};
