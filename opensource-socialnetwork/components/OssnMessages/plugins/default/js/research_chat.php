Ossn.RegisterStartupFunction(function () {

    function normalizeTopic(rawTopic){
        if(!rawTopic){
            return '';
        }

        let topic = String(rawTopic).toLowerCase().trim();

        if(topic.includes("memes")) return "Memes";
        if(topic.includes("food")) return "Food";
        if(topic.includes("finance")) return "Finance";
        if(topic.includes("health")) return "Health";
        if(topic.includes("books")) return "Books";
        if(topic.includes("fitness")) return "Fitness";
        if(topic.includes("fashion")) return "Fashion";
        if(topic.includes("education")) return "Education";
        if(topic.includes("technology")) return "Technology";
        if(topic.includes("art")) return "Art";
        if(topic.includes("pets")) return "Pets";
        if(topic.includes("politics")) return "Politics";
        if(topic.includes("gaming")) return "Gaming";
        if(topic.includes("music")) return "Music";
        if(topic.includes("photography")) return "Photography";
        if(topic.includes("travel")) return "Travel";

        return '';
    }

    const startersDB = {
         Memes: {
            friendly: [
                "Hey! I noticed we both enjoy memes. What kind always makes you laugh?",
                "Hi! Are you into chaotic memes or smart ones?",
                "What meme format never gets old for you?"
            ],
            professional: [
                "Hello, I noticed we both engage with meme culture online.",
                "It would be interesting to discuss how memes shape online conversations.",
                "I saw your engagement with meme posts and found it interesting."
            ],
            casual: [
                "What's the funniest meme you've seen recently?",
                "Do you save memes or just send them instantly?",
                "Are your memes more random or relatable?"
            ],
            icebreaker: [
                "If your life was a meme, what would the caption be?",
                "What's one meme you quote in real life?",
                "Which meme trend should come back?"
            ]
        },

        Food: {
            friendly: [
                "Hey! I saw you interact with food posts too. What’s your favorite cuisine?",
                "Hi! Are you more into cooking or eating?",
                "What’s the best dish you've ever had?"
            ],
            professional: [
                "I noticed we both engage with food-related content.",
                "It would be interesting to share culinary interests.",
                "I saw your interactions with food discussions."
            ],
            casual: [
                "What’s your go-to comfort food?",
                "Sweet or spicy?",
                "Do you like street food?"
            ],
            icebreaker: [
                "If you could eat one meal forever what would it be?",
                "Which country has the best food?",
                "Would you rather cook or order food?"
            ]
        },

        Finance: {
            friendly: [
                "Hey! I noticed we both follow finance topics. What interests you most there?",
                "Hi! Are you more into saving, investing, or market news?",
                "What got you interested in finance?"
            ],
            professional: [
                "Hello, I noticed we both engage with finance discussions.",
                "It would be interesting to exchange thoughts on current financial trends.",
                "I saw your interactions with finance-related content."
            ],
            casual: [
                "Do you follow market news regularly?",
                "Budgeting or investing — which do you enjoy more?",
                "What's one finance habit you think really helps?"
            ],
            icebreaker: [
                "If money was no issue, what would you invest in first?",
                "What's one finance myth people believe too easily?",
                "Do you think schools should teach finance better?"
            ]
        },

        Health: {
            friendly: [
                "Hey! I saw we both engage with health topics. What side of health interests you most?",
                "Hi! Are you more into wellness, nutrition, or mental health topics?",
                "What got you interested in health-related content?"
            ],
            professional: [
                "Hello, I noticed we both engage with health discussions.",
                "It would be interesting to exchange ideas on current health topics.",
                "I saw your interactions with health-related posts."
            ],
            casual: [
                "What's one healthy habit you're trying to keep?",
                "Do you focus more on sleep, food, or exercise?",
                "What's something simple that helps you feel better daily?"
            ],
            icebreaker: [
                "If you had to recommend one health habit to everyone, what would it be?",
                "What's more important: sleep or exercise?",
                "What's one health trend you're unsure about?"
            ]
        },

        Books: {
            friendly: [
                "Hey! I noticed we both like books. What kind do you enjoy most?",
                "Hi! What's a book you always recommend?",
                "What got you into reading?"
            ],
            professional: [
                "Hello, I noticed we both engage with book-related discussions.",
                "It would be interesting to exchange thoughts on books and reading habits.",
                "I saw your interactions with book content."
            ],
            casual: [
                "Fiction or non-fiction?",
                "Do you prefer physical books or ebooks?",
                "What's the last book you really enjoyed?"
            ],
            icebreaker: [
                "If you could live inside one book world, which would you choose?",
                "Which book character feels the most real to you?",
                "What's one book everyone should read once?"
            ]
        },

        Fitness: {
            friendly: [
                "Hey! I noticed we both follow fitness content. What type do you enjoy most?",
                "Hi! Are you more into gym, home workouts, or sports?",
                "What got you into fitness?"
            ],
            professional: [
                "Hello, I noticed we both engage with fitness discussions.",
                "It would be interesting to exchange views on fitness habits and goals.",
                "I saw your interactions with fitness-related posts."
            ],
            casual: [
                "Do you enjoy workouts or just the feeling after?",
                "Morning workouts or evening workouts?",
                "What's your favorite way to stay active?"
            ],
            icebreaker: [
                "If you could master one fitness skill instantly, what would it be?",
                "What's harder: consistency or motivation?",
                "What's one workout you secretly dislike?"
            ]
        },

        Fashion: {
            friendly: [
                "Hey! I noticed we both like fashion posts.",
                "What fashion trend are you loving lately?",
                "Do you prefer casual or formal styles?"
            ],
            professional: [
                "I saw your engagement with fashion discussions.",
                "It would be interesting to discuss fashion trends.",
                "I noticed we both follow fashion content."
            ],
            casual: [
                "What's your everyday outfit style?",
                "Favorite clothing brand?",
                "Do you follow fashion influencers?"
            ],
            icebreaker: [
                "If you could design clothes what style would it be?",
                "What trend should disappear forever?",
                "Vintage or modern fashion?"
            ]
        },

        Education: {
            friendly: [
                "Hey! I saw we both engage with education topics. What interests you most there?",
                "Hi! Are you more into learning methods or subject knowledge?",
                "What education topic do you enjoy discussing?"
            ],
            professional: [
                "Hello, I noticed we both engage with education discussions.",
                "It would be interesting to exchange views on education-related topics.",
                "I saw your interactions with education content."
            ],
            casual: [
                "What's something new you've learned recently?",
                "Do you prefer structured learning or learning on your own?",
                "What subject could you talk about for hours?"
            ],
            icebreaker: [
                "If you could master any subject instantly, what would it be?",
                "What's one thing school should teach better?",
                "Do you think learning is easier online or in person?"
            ]
        },

        Technology: {
            friendly: [
                "Hey! I saw you follow technology discussions too. What interests you the most?",
                "Hi! Looks like we both like tech — AI or cybersecurity?",
                "Hey there! Any cool tech projects you're exploring?"
            ],
            professional: [
                "Hello, I noticed we both engage with technology discussions.",
                "It would be great to exchange insights about tech trends.",
                "I saw your posts related to technology — very interesting."
            ],
            casual: [
                "Hey! Are you more into software or hardware?",
                "What tech gadget can’t you live without?",
                "Have you tried any new apps lately?"
            ],
            icebreaker: [
                "If you could build any tech startup what would it be?",
                "Which future tech excites you the most?",
                "Do you think AI will replace programmers?"
            ]
        },

        Art: {
            friendly: [
                "Hey! I saw you engage with art posts too.",
                "What type of art do you enjoy most?",
                "Do you create art yourself?"
            ],
            professional: [
                "I noticed we both engage with art discussions.",
                "It would be interesting to exchange thoughts on art.",
                "I saw your interactions with art content."
            ],
            casual: [
                "Painting or digital art?",
                "Favorite artist?",
                "Do you visit galleries?"
            ],
            icebreaker: [
                "If you could learn any art skill what would it be?",
                "Which painting fascinates you the most?",
                "Do you prefer modern or classic art?"
            ]
        },

        Pets: {
            friendly: [
                "Hey! I noticed you also like pets. Do you have any?",
                "Hi! I saw we both engage with pet posts — dog person or cat person?",
                "Hey there! What kind of pets do you like the most?"
            ],
            professional: [
                "Hello, I noticed we both follow discussions related to pets.",
                "It would be interesting to exchange thoughts about pet care.",
                "I saw your engagement with pet content — very interesting."
            ],
            casual: [
                "Hey! Are you more into cats or dogs?",
                "What’s the cutest pet you've ever seen?",
                "Do you have a favorite pet breed?"
            ],
            icebreaker: [
                "If you could adopt any animal tomorrow what would it be?",
                "Do you think dogs understand human emotions?",
                "What's the funniest pet video you've seen?"
            ]
        },

        Politics: {
            friendly: [
                "Hey! I noticed we both follow politics discussions. What interests you most there?",
                "Hi! Are you more interested in policy or current events?",
                "What got you into political discussions?"
            ],
            professional: [
                "Hello, I noticed we both engage with political discussions.",
                "It would be interesting to exchange perspectives on current political topics.",
                "I saw your interactions with politics-related content."
            ],
            casual: [
                "Do you follow politics daily or only major events?",
                "What's one political issue you think deserves more attention?",
                "Do you enjoy debates or prefer reading analysis?"
            ],
            icebreaker: [
                "If you could change one public policy tomorrow, what would it be?",
                "Do you think political discussions online help or hurt understanding?",
                "What's one issue people oversimplify too much?"
            ]
        },

        Gaming: {
            friendly: [
                "Hey! I noticed we both like gaming content. What do you play most?",
                "Hi! Are you more into story games or multiplayer games?",
                "What got you into gaming?"
            ],
            professional: [
                "Hello, I noticed we both engage with gaming discussions.",
                "It would be interesting to exchange thoughts on gaming trends and communities.",
                "I saw your interactions with gaming content."
            ],
            casual: [
                "PC, console, or mobile?",
                "What's your comfort game?",
                "Do you play more competitively or just for fun?"
            ],
            icebreaker: [
                "If you could live inside one game world, which would it be?",
                "What's one game you wish you could play again for the first time?",
                "Boss fights or open worlds?"
            ]
        },

        Music: {
            friendly: [
                "Hey! I noticed we both like music content. What do you listen to most?",
                "Hi! Do you have a favorite artist right now?",
                "What kind of music always works for you?"
            ],
            professional: [
                "Hello, I noticed we both engage with music discussions.",
                "It would be interesting to exchange views on music and listening habits.",
                "I saw your interactions with music-related posts."
            ],
            casual: [
                "Do you listen to music while working?",
                "What's your go-to genre?",
                "Any song you've been replaying lately?"
            ],
            icebreaker: [
                "If your week had a soundtrack, what song would be on it?",
                "Which artist would you see live instantly?",
                "What's one song you never skip?"
            ]
        },

        Photography: {
            friendly: [
                "Hey! I noticed we both enjoy photography content. What do you like capturing most?",
                "Hi! Are you more into portraits, landscapes, or street shots?",
                "What got you interested in photography?"
            ],
            professional: [
                "Hello, I noticed we both engage with photography discussions.",
                "It would be interesting to exchange thoughts on photography styles and techniques.",
                "I saw your interactions with photography content."
            ],
            casual: [
                "Phone camera or DSLR?",
                "Do you edit your photos a lot or keep them natural?",
                "What's your favorite type of photo to take?"
            ],
            icebreaker: [
                "If you could photograph anywhere tomorrow, where would you go?",
                "Black and white or color?",
                "What's one photo you've taken that you still really love?"
            ]
        },

        Travel: {
            friendly: [
                "Hey! I noticed we both enjoy travel content. What's your favorite kind of trip?",
                "Hi! Do you prefer beaches, cities, or mountains?",
                "What place do you most want to visit next?"
            ],
            professional: [
                "Hello, I noticed we both engage with travel discussions.",
                "It would be interesting to exchange perspectives on travel experiences and destinations.",
                "I saw your interactions with travel-related posts."
            ],
            casual: [
                "What's your dream destination right now?",
                "Do you plan everything or travel spontaneously?",
                "Window seat or aisle seat?"
            ],
            icebreaker: [
                "If you could teleport anywhere for one day, where would you go?",
                "What's the best trip you've ever had?",
                "Do you travel for food, views, or experiences?"
            ]
        }
    };



    document.addEventListener("click", function (e) {

        if (!e.target.classList.contains("tone-btn")) {
            return;
        }

        e.preventDefault();

        const tone = e.target.dataset.tone;
        const startersDiv = document.getElementById("conversation-starters");

        if (!startersDiv) {
            return;
        }

        let raw = Ossn.researchInterests || "";

        // split multiple interests
        let topics = raw.split(",").map(t => normalizeTopic(t.trim())).filter(t => t);

        startersDiv.innerHTML = "";

        if (!topics.length) {
            startersDiv.innerHTML = "<div class='starter-item'>No starters available.</div>";
            return;
        }

        let allStarters = [];

        topics.forEach(function(topic) {
            if (startersDB[topic] && startersDB[topic][tone]) {
                allStarters = allStarters.concat(startersDB[topic][tone]);
            }
        });

        // remove duplicates
        allStarters = [...new Set(allStarters)];

        if (!allStarters.length) {
            startersDiv.innerHTML = "<div class='starter-item'>No starters available.</div>";
            return;
        }

        const starters = allStarters;

        starters.forEach(function (text) {

            const item = document.createElement("div");
            item.className = "starter-item";
            item.innerText = text;

            item.onclick = function () {

            const input = document.querySelector("textarea[name=message]");

            if (!input) {
                return;
            }

            input.value = text;

            /* get receiver guid from message container */
            const container = document.querySelector(".message-inner");

            if(!container){
                return;
            }

            const guid = container.dataset.guid;

            /* trigger OSSN message send */
            if(typeof Ossn !== "undefined" && Ossn.SendMessage){
                Ossn.SendMessage(guid);
            }

            };

            startersDiv.appendChild(item);

        });

    });

});