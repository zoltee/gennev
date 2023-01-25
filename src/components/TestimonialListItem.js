import "./TestimonialListItem.css";

export default function TestimonialListItem({item}){
    return <>
        <div className="photo"><img src={item.imageUrl} title={item.name} alt={item.name} /></div>
        <div className="profile-comments">{item.comments}</div>
        </>

}