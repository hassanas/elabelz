Progos_messages plugin is used for sending and recieving messages between seller and reciever

it can perform following functions :

1. From product page a buyer can instentiate messages.
2. Seller and reciever can send and recieve messages.
3. seller and reciever can delete conversation


File structure


code/progos/messages

                  Blocks/messages.php  loading messages 
                  Blocks/conversation.php loading threads
                  
                  Controller/IndexController  Saving data and routing
                  
                  Models tables progos_messages , progos_attachment , progos_thread, progos_conversation